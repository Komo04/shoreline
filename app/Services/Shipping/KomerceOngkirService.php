<?php

namespace App\Services\Shipping;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Sentry\Laravel\Facade as Sentry;

class KomerceOngkirService
{
    /**
     * Hitung ongkir Komerce
     * - cache hasil sukses 10 menit
     * - simpan "last_ok" 3 hari untuk fallback saat API down
     */
    public function calculateDomesticCost(
        int $originId,
        int $destinationId,
        int $weight,
        string $courier
    ): array {

        $courier = strtolower(trim($courier));
        $weight  = max(1, (int) $weight);

        $cacheKey = $this->cacheKey($originId, $destinationId, $weight, $courier);
        $lastOkKey = $cacheKey . ':last_ok';

        // 1) cache cepat (10 menit)
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return array_merge($cached, [
                'cached' => true,
                'degraded' => false,
            ]);
        }

        // 2) call API
        $result = $this->callApi(
            $originId,
            $destinationId,
            $weight,
            $courier
        );

        // 3) sukses → simpan cache cepat + last_ok
        if (!empty($result['success'])) {

            $payload = array_merge($result, [
                'cached' => false,
                'degraded' => false,
            ]);

            Cache::put($cacheKey, $payload, now()->addMinutes(10));
            Cache::put($lastOkKey, $payload, now()->addDays(3));

            return $payload;
        }

        // 4) gagal → coba last_ok
        $lastOk = Cache::get($lastOkKey);
        if (is_array($lastOk) && !empty($lastOk['success'])) {
            Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($originId, $destinationId, $weight, $courier) {
                $scope->setTag('module', 'shipping');
                $scope->setTag('feature', 'komerce.calculateDomesticCost');
                $scope->setTag('fallback_type', 'last_ok_cache');
                $scope->setExtra('origin_id', $originId);
                $scope->setExtra('destination_id', $destinationId);
                $scope->setExtra('weight', $weight);
                $scope->setExtra('courier', $courier);
            });

            Sentry::captureMessage('Komerce ongkir menggunakan fallback cache terakhir', \Sentry\Severity::warning());

            return array_merge($lastOk, [
                'cached' => true,
                'degraded' => true,
                'message' => 'API ongkir sedang gangguan. Menggunakan ongkir terakhir (cache).',
                'fallback_type' => 'last_ok_cache',
            ]);
        }

        // 5) gagal + tidak ada last_ok → fallback flat (jika enabled)
        $fallbackEnabled = (bool) config('services.shipping.fallback_enabled', true);

        if ($fallbackEnabled) {
            Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($originId, $destinationId, $weight, $courier) {
                $scope->setTag('module', 'shipping');
                $scope->setTag('feature', 'komerce.calculateDomesticCost');
                $scope->setTag('fallback_type', 'flat_rate');
                $scope->setExtra('origin_id', $originId);
                $scope->setExtra('destination_id', $destinationId);
                $scope->setExtra('weight', $weight);
                $scope->setExtra('courier', $courier);
            });

            Sentry::captureMessage('Komerce ongkir menggunakan flat fallback rate', \Sentry\Severity::warning());

            return $this->flatFallbackResult(
                'API ongkir sedang gangguan. Menggunakan ongkir fallback (flat rate).',
                $courier
            );
        }

        // 6) fallback dimatikan → return error asli
        return array_merge($result, [
            'cached' => false,
            'degraded' => false,
        ]);
    }

    /**
     * ====================================================
     * CALL KOMERCE API
     * ====================================================
     */
    private function callApi(
        int $originId,
        int $destinationId,
        int $weight,
        string $courier
    ): array {

        $baseUrl = rtrim((string) config('services.komerce.base_url'), '/');
        $apiKey  = (string) config('services.komerce.api_key');

        if ($baseUrl === '') {
            return [
                'success' => false,
                'message' => 'KOMERCE base URL belum di-set di .env',
            ];
        }

        if (!$apiKey) {
            Sentry::captureMessage('KOMERCE_API_KEY belum di-set di .env', \Sentry\Severity::error());

            return [
                'success' => false,
                'message' => 'KOMERCE_API_KEY belum di-set di .env',
            ];
        }

        // (opsional) log config check — sebaiknya matikan di production

        try {
            /** @var Response $res */
            $res = Http::asForm()
                ->timeout(5)      // ✅ percepat failover
                ->retry(1, 150)    // ✅ jangan kebanyakan retry saat down
                ->withHeaders([
                    'key' => $apiKey,
                    'accept' => 'application/json',
                ])
                ->post($baseUrl . '/calculate/domestic-cost', [
                    'origin' => $originId,
                    'destination' => $destinationId,
                    'weight' => $weight,
                    'courier' => $courier,
                ]);
        } catch (\Throwable $e) {
            Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($originId, $destinationId, $weight, $courier, $baseUrl) {
                $scope->setTag('module', 'shipping');
                $scope->setTag('feature', 'komerce.callApi');
                $scope->setTag('shipping_status', 'exception');
                $scope->setExtra('origin_id', $originId);
                $scope->setExtra('destination_id', $destinationId);
                $scope->setExtra('weight', $weight);
                $scope->setExtra('courier', $courier);
                $scope->setExtra('base_url', $baseUrl);
            });

            Sentry::captureException($e);

            Log::error('Komerce API exception', [
                'message' => $e->getMessage(),
                'origin_id' => $originId,
                'destination_id' => $destinationId,
                'weight' => $weight,
                'courier' => $courier,
            ]);

            return [
                'success' => false,
                'message' => 'Komerce API exception: ' . $e->getMessage()
            ];
        }

        // safe json parse
        $json = null;
        try {
            $json = $res->json();
        } catch (\Throwable $e) {
            Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($originId, $destinationId, $weight, $courier) {
                $scope->setTag('module', 'shipping');
                $scope->setTag('feature', 'komerce.callApi');
                $scope->setTag('shipping_status', 'invalid_json');
                $scope->setExtra('origin_id', $originId);
                $scope->setExtra('destination_id', $destinationId);
                $scope->setExtra('weight', $weight);
                $scope->setExtra('courier', $courier);
            });

            Sentry::captureMessage('Response Komerce bukan JSON yang valid', \Sentry\Severity::warning());

            Log::warning('Komerce response bukan JSON', [
                'body' => $res->body()
            ]);

            $json = ['raw' => $res->body()];
        }

        // daily limit detection
        $metaMsg = strtolower((string) data_get($json, 'meta.message', ''));
        if (str_contains($metaMsg, 'daily limit')) {
            Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($originId, $destinationId, $weight, $courier) {
                $scope->setTag('module', 'shipping');
                $scope->setTag('feature', 'komerce.callApi');
                $scope->setTag('shipping_status', 'daily_limit_reached');
                $scope->setExtra('origin_id', $originId);
                $scope->setExtra('destination_id', $destinationId);
                $scope->setExtra('weight', $weight);
                $scope->setExtra('courier', $courier);
            });

            Sentry::captureMessage('Komerce daily limit reached', \Sentry\Severity::warning());

            Log::warning('Komerce daily limit reached');

            return [
                'success' => false,
                'http_status' => $res->status() ?: 429,
                'message' => 'Daily limit Komerce habis.',
                'body' => $json
            ];
        }

        // http error
        if ($res->failed()) {
            Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($originId, $destinationId, $weight, $courier, $res) {
                $scope->setTag('module', 'shipping');
                $scope->setTag('feature', 'komerce.callApi');
                $scope->setTag('shipping_status', 'http_failed');
                $scope->setExtra('origin_id', $originId);
                $scope->setExtra('destination_id', $destinationId);
                $scope->setExtra('weight', $weight);
                $scope->setExtra('courier', $courier);
                $scope->setExtra('http_status', $res->status());
            });

            Sentry::captureMessage('Komerce API mengembalikan HTTP error', \Sentry\Severity::warning());

            Log::warning('Komerce API error', [
                'status' => $res->status(),
                'response' => $json
            ]);

            return [
                'success' => false,
                'http_status' => $res->status(),
                'message' => (string)(
                    data_get($json, 'meta.message')
                    ?: 'Gagal mengambil ongkir'
                ),
                'body' => $json
            ];
        }

        // API-level error (meski HTTP 200)
        $metaStatusRaw = data_get($json, 'meta.status');
        $metaStatus = is_string($metaStatusRaw)
            ? in_array(strtolower($metaStatusRaw), ['1', 'true', 'ok', 'success'], true)
            : (bool) $metaStatusRaw;

        if (! $metaStatus) {
            return [
                'success' => false,
                'http_status' => $res->status(),
                'message' => (string) (data_get($json, 'meta.message') ?: 'Komerce mengembalikan status gagal'),
                'body' => $json,
            ];
        }

        // Guard: pastikan payload options ada sebelum dianggap sukses
        $options = data_get($json, 'data.data', data_get($json, 'data', []));
        if (! is_array($options)) {
            return [
                'success' => false,
                'http_status' => $res->status(),
                'message' => 'Format response Komerce tidak sesuai.',
                'body' => $json,
            ];
        }

        return [
            'success' => true,
            'http_status' => $res->status(),
            'data' => $json
        ];
    }

    /**
     * Fallback response (flat) dibuat kompatibel:
     * CheckoutController membaca options dari data.data
     */
    private function flatFallbackResult(string $msg, string $courier): array
    {
        $flat = (int) config('services.shipping.fallback_flat', 20000);
        $etd  = (string) config('services.shipping.fallback_etd', '2-5 hari');

        return [
            'success' => true,
            'http_status' => 200,
            'cached' => false,
            'degraded' => true,
            'fallback_type' => 'flat_rate',
            'message' => $msg,
            'data' => [
                // dibuat mirip struktur API: data.data = options
                'data' => $this->manualFallbackServices($courier, $flat, $etd),
                'meta' => [
                    'message' => $msg,
                ],
            ],
        ];
    }

    private function manualFallbackServices(string $courier, int $flat, string $defaultEtd): array
    {
        $economy = max(10000, $flat - 5000);
        $regular = max($economy + 5000, $flat);
        $fast = $regular + 10000;

        $map = [
            'jne' => [
                ['service' => 'JNE OKE', 'description' => 'Ongkir manual ekonomi sementara', 'cost' => $economy, 'etd' => '3-5 hari'],
                ['service' => 'JNE REG', 'description' => 'Ongkir manual reguler sementara', 'cost' => $regular, 'etd' => $defaultEtd],
                ['service' => 'JNE YES', 'description' => 'Ongkir manual cepat sementara', 'cost' => $fast, 'etd' => '1-2 hari'],
            ],
            'jnt' => [
                ['service' => 'JNT ECO', 'description' => 'Ongkir manual ekonomi sementara', 'cost' => $economy, 'etd' => '3-5 hari'],
                ['service' => 'JNT EZ', 'description' => 'Ongkir manual reguler sementara', 'cost' => $regular, 'etd' => $defaultEtd],
                ['service' => 'JNT EXPRESS', 'description' => 'Ongkir manual cepat sementara', 'cost' => $fast, 'etd' => '1-2 hari'],
            ],
            'pos' => [
                ['service' => 'POS HEMAT', 'description' => 'Ongkir manual hemat sementara', 'cost' => $economy, 'etd' => '3-6 hari'],
                ['service' => 'POS REGULER', 'description' => 'Ongkir manual reguler sementara', 'cost' => $regular, 'etd' => $defaultEtd],
                ['service' => 'POS KILAT', 'description' => 'Ongkir manual cepat sementara', 'cost' => $fast, 'etd' => '1-2 hari'],
            ],
        ];

        $services = $map[$courier] ?? [
            ['service' => strtoupper($courier) . ' REG', 'description' => 'Ongkir manual reguler sementara', 'cost' => $regular, 'etd' => $defaultEtd],
        ];

        return array_map(function (array $service) use ($courier) {
            $service['courier'] = $courier;
            $service['code'] = $courier;

            return $service;
        }, $services);
    }

    /**
     * Cache key generator
     */
    private function cacheKey(
        int $originId,
        int $destinationId,
        int $weight,
        string $courier
    ): string {
        return 'ongkir:komerce:' . md5(implode('|', [
            $originId,
            $destinationId,
            $weight,
            $courier
        ]));
    }
}


<?php

namespace App\Services\Shipping;

use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class KomerceTrackingService
{
    public function track(string $awb, string $courier): array
    {
        $awb = trim($awb);
        $courier = strtolower(trim($courier));

        if ($awb === '' || $courier === '') {
            return [
                'success' => false,
                'message' => 'Resi atau kurir kosong.',
                'data' => null,
            ];
        }

        $baseUrl = rtrim((string) config('services.komerce.base_url'), '/');
        $apiKey  = (string) config('services.komerce.api_key');

        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'KOMERCE_API_KEY belum di-set di .env',
                'data' => null,
            ];
        }

        $url = $baseUrl . '/track/waybill';

        try {
            /** @var Response $res */
            $res = Http::timeout(20)
                ->retry(2, 250)
                ->withHeaders([
                    'key' => $apiKey,
                    'accept' => 'application/json',
                ])
                ->post($url, [
                    'awb' => $awb,
                    'courier' => $courier,
                ]);

            if (!$res->ok()) {
                return [
                    'success' => false,
                    'message' => 'Tracking gagal (HTTP ' . $res->status() . ')',
                    'data' => null,
                    'raw' => $res->json(),
                ];
            }

            $json = $res->json();

            $metaRaw = data_get($json, 'meta.status', false);
            $metaOk = is_string($metaRaw)
                ? in_array(strtolower($metaRaw), ['1', 'true', 'ok', 'success'], true)
                : (bool) $metaRaw;
            if (!$metaOk) {
                return [
                    'success' => false,
                    'message' => (string) data_get($json, 'meta.message', 'AWB tidak ditemukan / error'),
                    'data' => null,
                    'raw' => $json,
                ];
            }

            $data = data_get($json, 'data');

            $manifest = (array) data_get($data, 'manifest', []);
            $timeline = collect($manifest)->map(function ($m) {
                $date = trim((string)($m['manifest_date'] ?? ''));
                $time = trim((string)($m['manifest_time'] ?? ''));
                $dtRaw = trim($date . ' ' . $time);

                $tzFrom = 'Asia/Jakarta';   // ✅ asumsi data Komerce WIB
                $tzTo   = 'Asia/Makassar';  // ✅ tampil WITA

                $dtIso = null;
                $dtWita = $dtRaw;

                if ($dtRaw !== '') {
                    try {
                        $dt = Carbon::createFromFormat('Y-m-d H:i:s', $dtRaw, $tzFrom)->timezone($tzTo);
                        $dtIso  = $dt->toIso8601String();
                        $dtWita = $dt->format('Y-m-d H:i');
                    } catch (\Throwable $e) {
                        // kalau format beda, coba parse umum
                        try {
                            $dt = Carbon::parse($dtRaw, $tzFrom)->timezone($tzTo);
                            $dtIso  = $dt->toIso8601String();
                            $dtWita = $dt->format('Y-m-d H:i');
                        } catch (\Throwable $e2) {
                        }
                    }
                }

                return [
                    'datetime_raw'  => $dtRaw,
                    'datetime_iso'  => $dtIso,
                    'datetime_wita' => $dtWita,
                    'datetime'      => $dtWita, // fallback kompatibilitas
                    'city' => (string)($m['city_name'] ?? ''),
                    'code' => (string)($m['manifest_code'] ?? ''),
                    'description' => (string)($m['manifest_description'] ?? ''),
                ];
            })->values()->all();

            return [
                'success' => true,
                'message' => (string) data_get($json, 'meta.message', 'OK'),
                'data' => [
                    'delivered' => (bool) data_get($data, 'delivered', false),
                    'summary' => data_get($data, 'summary', []),
                    'details' => data_get($data, 'details', []),
                    'delivery_status' => data_get($data, 'delivery_status', []),
                    'timeline' => $timeline,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }
}

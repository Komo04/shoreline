<?php

namespace App\Services\Midtrans;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Midtrans\Config;
use Midtrans\Snap;
use Sentry\Laravel\Facade as Sentry;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey    = config('services.midtrans.server_key');
        Config::$isProduction = $this->isProduction();
        Config::$isSanitized  = (bool) config('services.midtrans.is_sanitized', true);
        Config::$is3ds        = (bool) config('services.midtrans.is_3ds', true);
    }

    public function getSnapToken(array $params): string
    {
        return Snap::getSnapToken($params);
    }

    public function serverKey(): string
    {
        $key = (string) config('services.midtrans.server_key');

        if ($key === '') {
            Sentry::withScope(function (\Sentry\State\Scope $scope): void {
                $scope->setTag('module', 'midtrans');
                $scope->setTag('feature', 'midtrans.serverKey');
                $scope->setTag('midtrans_status', 'missing_server_key');
                Sentry::captureMessage('MIDTRANS_SERVER_KEY belum di-set', \Sentry\Severity::error());
            });

            throw new \RuntimeException('MIDTRANS_SERVER_KEY belum di-set');
        }

        return $key;
    }

    public function clientKey(): string
    {
        return (string) config('services.midtrans.client_key');
    }

    public function snapJsUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    public function apiBaseUrl(): string
    {
        return $this->isProduction()
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    private function isProduction(): bool
    {
        $configured = (bool) config('services.midtrans.is_production');
        $appUrl = strtolower((string) config('app.url', ''));

        if (str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1')) {
            return false;
        }

        return $configured;
    }

    /**
     * Status transaksi Midtrans:
     * - Auth benar + order_id salah => biasanya body.status_code = 404 (HTTP bisa 200)
     */
    public function getStatus(string $orderId): array
    {
        try {
            /** @var Response $resp */
            $resp = Http::withBasicAuth($this->serverKey(), '')
                ->acceptJson()
                ->timeout(15)
                ->get($this->apiBaseUrl() . "/v2/{$orderId}/status");

            $body = $resp->json() ?? [];
            $statusCode = (string) data_get($body, 'status_code', '');

            $ok = $resp->successful() && in_array($statusCode, ['200', '201'], true);

            if (! $ok) {
                Sentry::withScope(function (\Sentry\State\Scope $scope) use ($orderId, $resp, $statusCode): void {
                    $scope->setTag('module', 'midtrans');
                    $scope->setTag('feature', 'midtrans.getStatus');
                    $scope->setTag('midtrans_status', 'failed_status_check');
                    $scope->setExtra('order_id', $orderId);
                    $scope->setExtra('http_status', $resp->status());
                    $scope->setExtra('midtrans_status_code', $statusCode);
                    Sentry::captureMessage('Midtrans getStatus mengembalikan status tidak sukses', \Sentry\Severity::warning());
                });
            }

            return [
                'ok' => $ok,
                'http_status' => $resp->status(),
                'body' => $body,
                'raw' => $resp->body(),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Sentry::withScope(function (\Sentry\State\Scope $scope) use ($orderId, $e): void {
                $scope->setTag('module', 'midtrans');
                $scope->setTag('feature', 'midtrans.getStatus');
                $scope->setTag('midtrans_status', 'exception');
                $scope->setExtra('order_id', $orderId);
                Sentry::captureException($e);
            });

            return [
                'ok' => false,
                'http_status' => 0,
                'body' => null,
                'raw' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refund Midtrans:
     * Penting: Midtrans kadang balas HTTP 200 tapi body.status_code = 412 (gagal)
     * Jadi kita anggap sukses hanya jika body.status_code = 200/201.
     */
    public function refund(string $orderId, string $refundKey, int $amount, string $reason): array
    {
        $payload = [
            'refund_key' => $refundKey,
            'amount'     => $amount,
            'reason'     => $reason,
        ];

        try {
            $url = $this->apiBaseUrl() . "/v2/{$orderId}/refund";

            /** @var Response $resp */
            $resp = Http::withBasicAuth($this->serverKey(), '')
                ->acceptJson()
                ->timeout(15)
                ->post($url, $payload);

            // fallback akun lama kalau 404
            if ($resp->status() === 404) {
                Sentry::withScope(function (\Sentry\State\Scope $scope) use ($orderId, $refundKey, $amount): void {
                    $scope->setTag('module', 'midtrans');
                    $scope->setTag('feature', 'midtrans.refund');
                    $scope->setTag('midtrans_status', 'refund_fallback_endpoint');
                    $scope->setExtra('order_id', $orderId);
                    $scope->setExtra('refund_key', $refundKey);
                    $scope->setExtra('amount', $amount);
                    Sentry::captureMessage('Midtrans refund endpoint /refund mengembalikan 404, mencoba /refund-1', \Sentry\Severity::warning());
                });

                $resp = Http::withBasicAuth($this->serverKey(), '')
                    ->acceptJson()
                    ->timeout(15)
                    ->post($this->apiBaseUrl() . "/v2/{$orderId}/refund-1", $payload);
            }

            $body = $resp->json() ?? [];
            $statusCode = (string) data_get($body, 'status_code', '');

            $ok = $resp->successful() && in_array($statusCode, ['200', '201'], true);

            if (! $ok) {
                Sentry::withScope(function (\Sentry\State\Scope $scope) use ($orderId, $refundKey, $amount, $resp, $statusCode): void {
                    $scope->setTag('module', 'midtrans');
                    $scope->setTag('feature', 'midtrans.refund');
                    $scope->setTag('midtrans_status', 'refund_failed');
                    $scope->setExtra('order_id', $orderId);
                    $scope->setExtra('refund_key', $refundKey);
                    $scope->setExtra('amount', $amount);
                    $scope->setExtra('http_status', $resp->status());
                    $scope->setExtra('midtrans_status_code', $statusCode);
                    Sentry::captureMessage('Midtrans refund mengembalikan status tidak sukses', \Sentry\Severity::warning());
                });
            }

            return [
                'ok' => $ok,
                'http_status' => $resp->status(),
                'body' => $body,
                'raw' => $resp->body(),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Sentry::withScope(function (\Sentry\State\Scope $scope) use ($orderId, $refundKey, $amount, $e): void {
                $scope->setTag('module', 'midtrans');
                $scope->setTag('feature', 'midtrans.refund');
                $scope->setTag('midtrans_status', 'exception');
                $scope->setExtra('order_id', $orderId);
                $scope->setExtra('refund_key', $refundKey);
                $scope->setExtra('amount', $amount);
                Sentry::captureException($e);
            });

            return [
                'ok' => false,
                'http_status' => 0,
                'body' => null,
                'raw' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey());
        return hash_equals($expected, $signatureKey);
    }
}

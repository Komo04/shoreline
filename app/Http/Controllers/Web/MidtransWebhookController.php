<?php

namespace App\Http\Controllers\Web;

use App\Models\Pembayaran;
use App\Models\RefundRequest;
use App\Models\Transaksi;
use App\Notifications\UserStatusPesananDatabaseNotification;
use App\Notifications\UserStatusPesananDiupdate;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Sentry\Laravel\Facade as Sentry;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Log ringkas (hindari log semua payload mentah di production)
        Log::info('MIDTRANS WEBHOOK HIT', [
            'order_id'           => $request->input('order_id'),
            'transaction_status' => $request->input('transaction_status'),
            'status_code'        => $request->input('status_code'),
            'gross_amount'       => $request->input('gross_amount'),
            'transaction_id'     => $request->input('transaction_id'),
            'payment_type'       => $request->input('payment_type'),
            'fraud_status'       => $request->input('fraud_status'),
        ]);

        $orderId     = (string) $request->input('order_id', '');
        $statusCode  = (string) $request->input('status_code', '');
        $grossAmount = (string) $request->input('gross_amount', '');
        $signature   = (string) $request->input('signature_key', '');

        // Payload minimum Midtrans
        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signature === '') {
            Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($orderId, $statusCode, $grossAmount, $signature) {
                $scope->setTag('module', 'midtrans');
                $scope->setTag('feature', 'midtrans.webhook');
                $scope->setTag('webhook_status', 'invalid_payload');
                $scope->setExtra('order_id', $orderId);
                $scope->setExtra('status_code', $statusCode);
                $scope->setExtra('gross_amount', $grossAmount);
                $scope->setExtra('has_signature', $signature !== '');
            });

            Sentry::captureMessage('Midtrans webhook menerima payload tidak lengkap', \Sentry\Severity::warning());

            Log::warning('MIDTRANS EMPTY/INVALID PAYLOAD', [
                'orderId' => $orderId,
                'statusCode' => $statusCode,
                'grossAmount' => $grossAmount,
                'has_signature' => $signature !== '',
            ]);

            return response()->json(['message' => 'Bad Request'], 400);
        }

        // Ignore Midtrans test notifications
        if (str_starts_with($orderId, 'payment_notif_test_')) {
            Log::info('MIDTRANS TEST NOTIFICATION IGNORED', ['order_id' => $orderId]);
            return response()->json(['message' => 'IGNORED TEST'], 200);
        }

        // Signature verify
        $serverKey = (string) config('services.midtrans.server_key');
        if ($serverKey === '') {
            Sentry::captureMessage('MIDTRANS SERVER KEY EMPTY - cannot verify signature', \Sentry\Severity::error());

            Log::error('MIDTRANS SERVER KEY EMPTY - cannot verify signature');

            return response()->json(['message' => 'Invalid configuration'], 500);
        }

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if (!hash_equals($expected, $signature)) {
            Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($orderId, $statusCode, $grossAmount) {
                $scope->setTag('module', 'midtrans');
                $scope->setTag('feature', 'midtrans.webhook');
                $scope->setTag('webhook_status', 'invalid_signature');
                $scope->setExtra('order_id', $orderId);
                $scope->setExtra('status_code', $statusCode);
                $scope->setExtra('gross_amount', $grossAmount);
            });

            Sentry::captureMessage('Midtrans webhook signature tidak valid', \Sentry\Severity::warning());

            Log::warning('MIDTRANS INVALID SIGNATURE', [
                'orderId' => $orderId,
                'statusCode' => $statusCode,
                'grossAmount' => $grossAmount,
            ]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $transactionStatus = (string) $request->input('transaction_status', '');
        $fraudStatus       = (string) $request->input('fraud_status', '');
        $paymentType       = (string) $request->input('payment_type', '');
        $transactionId     = (string) $request->input('transaction_id', '');

        try {
            DB::transaction(function () use (
                $orderId,
                $statusCode,
                $grossAmount,
                $transactionStatus,
                $fraudStatus,
                $paymentType,
                $transactionId
            ) {
                /** @var Transaksi|null $trx */
                $trx = Transaksi::where('midtrans_order_id', $orderId)
                    ->orWhere('kode_transaksi', $orderId)
                    ->lockForUpdate()
                    ->first();

                if (!$trx) {
                    Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($orderId, $transactionStatus, $paymentType, $transactionId) {
                        $scope->setTag('module', 'midtrans');
                        $scope->setTag('feature', 'midtrans.webhook');
                        $scope->setTag('webhook_status', 'order_not_found');
                        $scope->setExtra('order_id', $orderId);
                        $scope->setExtra('transaction_status', $transactionStatus);
                        $scope->setExtra('payment_type', $paymentType);
                        $scope->setExtra('transaction_id', $transactionId);
                    });

                    Sentry::captureMessage('Midtrans webhook order tidak ditemukan di database', \Sentry\Severity::warning());

                    Log::warning('MIDTRANS ORDER NOT FOUND', ['order_id' => $orderId]);
                    return;
                }

                // Simpan data midtrans ke transaksi
                if ($trx->midtrans_order_id !== $orderId) {
                    $trx->midtrans_order_id = $orderId;
                }
                if ($transactionId) $trx->midtrans_transaction_id = $transactionId;
                if ($paymentType)   $trx->midtrans_payment_type   = $paymentType;
                $trx->save();

                /**
                 * =====================================================
                 * 1) REFUND EVENT HANDLING (refund / partial_refund)
                 * =====================================================
                 */
                if (in_array($transactionStatus, ['refund', 'partial_refund'], true)) {

                    $refund = RefundRequest::where('transaksi_id', $trx->id)
                        ->where('method', RefundRequest::METHOD_MIDTRANS)
                        ->whereIn('status', [
                            RefundRequest::STATUS_REQUESTED,
                            RefundRequest::STATUS_PROCESSING,
                        ])
                        ->latest()
                        ->lockForUpdate()
                        ->first();

                    if (!$refund) {
                        Sentry::configureScope(function (\Sentry\State\Scope $scope) use ($trx, $orderId, $transactionStatus) {
                            $scope->setTag('module', 'midtrans');
                            $scope->setTag('feature', 'midtrans.webhook');
                            $scope->setTag('webhook_status', 'refund_request_not_found');
                            $scope->setExtra('transaksi_id', $trx->id);
                            $scope->setExtra('order_id', $orderId);
                            $scope->setExtra('transaction_status', $transactionStatus);
                        });

                        Sentry::captureMessage('Midtrans refund event diterima tetapi refund request tidak ditemukan', \Sentry\Severity::warning());

                        Log::warning('MIDTRANS REFUND EVENT BUT NO REFUND REQUEST FOUND', [
                            'transaksi_id' => $trx->id,
                            'order_id' => $orderId,
                            'transaction_status' => $transactionStatus,
                        ]);

                        return;
                    }

                    // PARTIAL REFUND
                    if ($transactionStatus === 'partial_refund') {

                        $refund->update([
                            'status' => 'partial_refunded', // pastikan UI kamu handle
                            'midtrans_response' => [
                                'order_id'            => $orderId,
                                'transaction_status'  => $transactionStatus,
                                'transaction_id'      => $transactionId,
                                'payment_type'        => $paymentType,
                                'status_code'         => $statusCode,
                                'gross_amount'        => $grossAmount,
                                'fraud_status'        => $fraudStatus,
                            ],
                            'synced_at' => now(),
                        ]);

                        Pembayaran::updateOrCreate(
                            ['transaksi_id' => $trx->id],
                            [
                                'metode_pembayaran'  => 'midtrans',
                                'total_pembayaran'   => $trx->total_pembayaran,
                                'status_pembayaran'  => 'partial_refund',
                                'tanggal_pembayaran' => $trx->paid_at ?? now(),
                                'bukti_transfer'     => null,
                            ]
                        );

                        $oldStatus = (string) $trx->status_transaksi;
                        $trx->status_transaksi = 'partial_refund';
                        $trx->save();

                        $trx->load('user');
                        if ($trx->user && $oldStatus !== 'partial_refund') {
                            $user = $trx->user;
                            DB::afterCommit(function () use ($user, $trx, $oldStatus) {
                                $user->notify(new UserStatusPesananDatabaseNotification($trx, $oldStatus, 'partial_refund'));
                                $user->notify(new UserStatusPesananDiupdate($trx, $oldStatus, 'partial_refund'));
                            });
                        }

                        // Partial refund: stok biasanya tricky (karena qty/amount parsial).
                        // Kamu bisa handle restore stok parsial kalau punya datanya.
                        Log::warning('PARTIAL_REFUND RECEIVED - STOCK NOT RESTORED (manual check needed)', [
                            'transaksi_id' => $trx->id,
                            'refund_id' => $refund->id,
                        ]);

                        return;
                    }

                    // FULL REFUND
                    $refund->update([
                        'status'      => RefundRequest::STATUS_REFUNDED,
                        'synced_at'   => now(),
                        'refunded_at' => $refund->refunded_at ?? now(),
                        'midtrans_response' => [
                            'order_id'            => $orderId,
                            'transaction_status'  => $transactionStatus,
                            'transaction_id'      => $transactionId,
                            'payment_type'        => $paymentType,
                            'status_code'         => $statusCode,
                            'gross_amount'        => $grossAmount,
                            'fraud_status'        => $fraudStatus,
                        ],
                    ]);

                    Pembayaran::updateOrCreate(
                        ['transaksi_id' => $trx->id],
                        [
                            'metode_pembayaran'  => 'midtrans',
                            'total_pembayaran'   => $trx->total_pembayaran,
                            'status_pembayaran'  => 'refund',
                            'tanggal_pembayaran' => $trx->paid_at ?? now(),
                            'bukti_transfer'     => null,
                        ]
                    );

                    // Restore stok idempotent
                    if (!$refund->stock_restored_at) {
                        app(StockService::class)->restore($trx, 'REFUND MIDTRANS (refund_id:' . $refund->id . ')');
                        $refund->update(['stock_restored_at' => now()]);
                    }

                    $oldStatus = (string) $trx->status_transaksi;
                    $trx->status_transaksi = 'refund';
                    $trx->save();

                    $trx->load('user');
                    if ($trx->user && $oldStatus !== 'refund') {
                        $user = $trx->user;
                        DB::afterCommit(function () use ($user, $trx, $oldStatus) {
                            $user->notify(new UserStatusPesananDatabaseNotification($trx, $oldStatus, 'refund'));
                            $user->notify(new UserStatusPesananDiupdate($trx, $oldStatus, 'refund'));
                        });
                    }

                    return;
                }

                /**
                 * =====================================================
                 * 2) Jika sedang refund_processing lalu ada status gagal
                 * (deny/cancel/expire/failure) -> tandai refund gagal
                 * =====================================================
                 */
                if (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'], true)) {

                    $refund = RefundRequest::where('transaksi_id', $trx->id)
                        ->where('method', RefundRequest::METHOD_MIDTRANS)
                        ->where('status', RefundRequest::STATUS_PROCESSING)
                        ->latest()
                        ->lockForUpdate()
                        ->first();

                    if ($refund) {
                        $refund->update([
                            'status' => RefundRequest::STATUS_FAILED,
                            'synced_at' => now(),
                            'midtrans_response' => [
                                'order_id'            => $orderId,
                                'transaction_status'  => $transactionStatus,
                                'transaction_id'      => $transactionId,
                                'payment_type'        => $paymentType,
                                'status_code'         => $statusCode,
                                'gross_amount'        => $grossAmount,
                                'fraud_status'        => $fraudStatus,
                            ],
                        ]);
                    }
                }

                // Kalau transaksi sudah mode refund, jangan di-override mapping normal
                if (in_array($trx->status_transaksi, ['refund', 'refund_processing', 'partial_refund'], true)) {
                    return;
                }

                /**
                 * =====================================================
                 * 3) NORMAL PAYMENT STATUS HANDLING
                 * =====================================================
                 */
                $oldStatus = (string) $trx->status_transaksi;

                $statusTransaksi  = (string) $trx->status_transaksi;
                $statusPembayaran = 'pending';

                // Mapping Midtrans:
                // capture -> cc (cek fraud_status)
                // settlement -> paid
                // pending -> pending
                // expire -> expired
                // cancel/deny/failure -> dibatalkan/ditolak
                if ($transactionStatus === 'capture') {

                    if ($fraudStatus === 'accept') {
                        $statusTransaksi  = 'paid';
                        $statusPembayaran = 'paid';
                        if (!$trx->paid_at) $trx->paid_at = now();
                    } else {
                        $statusTransaksi  = 'pending';
                        $statusPembayaran = 'pending';
                    }
                } elseif ($transactionStatus === 'settlement') {

                    $statusTransaksi  = 'paid';
                    $statusPembayaran = 'paid';
                    if (!$trx->paid_at) $trx->paid_at = now();
                } elseif ($transactionStatus === 'pending') {

                    $statusTransaksi  = 'pending';
                    $statusPembayaran = 'pending';
                } elseif ($transactionStatus === 'expire') {

                    $statusTransaksi  = 'expired';
                    $statusPembayaran = 'expired';
                } elseif (in_array($transactionStatus, ['cancel', 'deny', 'failure'], true)) {

                    $statusTransaksi  = 'dibatalkan';
                    $statusPembayaran = 'ditolak';
                }

                // Update transaksi
                $trx->status_transaksi = $statusTransaksi;
                $trx->save();

                // Deduct stock only once when entering paid
                if ($oldStatus !== 'paid' && $trx->status_transaksi === 'paid') {
                    app(StockService::class)->deductWhenPaid($trx);
                }

                // Notify user if changed
                $trx->load('user');
                if ($trx->user && $oldStatus !== $trx->status_transaksi) {
                    $user = $trx->user;
                    DB::afterCommit(function () use ($user, $trx, $oldStatus) {
                        $user->notify(new UserStatusPesananDatabaseNotification($trx, $oldStatus, $trx->status_transaksi));
                        $user->notify(new UserStatusPesananDiupdate($trx, $oldStatus, $trx->status_transaksi));
                    });
                }

                // Update pembayaran
                Pembayaran::updateOrCreate(
                    ['transaksi_id' => $trx->id],
                    [
                        'metode_pembayaran'  => 'midtrans',
                        'total_pembayaran'   => $trx->total_pembayaran,
                        'status_pembayaran'  => $statusPembayaran,
                        'tanggal_pembayaran' => ($statusPembayaran === 'paid')
                            ? ($trx->paid_at ?? now())
                            : null,
                        'bukti_transfer'     => null,
                    ]
                );
            });

            // Midtrans butuh 200 agar tidak retry berulang
            return response()->json(['message' => 'OK'], 200);
        } catch (\Throwable $e) {
            Sentry::configureScope(function (\Sentry\State\Scope $scope) use (
                $orderId,
                $transactionStatus,
                $paymentType,
                $transactionId,
                $statusCode,
                $grossAmount
            ) {
                $scope->setTag('module', 'midtrans');
                $scope->setTag('feature', 'midtrans.webhook');
                $scope->setTag('webhook_status', 'exception');
                $scope->setExtra('order_id', $orderId);
                $scope->setExtra('transaction_status', $transactionStatus);
                $scope->setExtra('payment_type', $paymentType);
                $scope->setExtra('transaction_id', $transactionId);
                $scope->setExtra('status_code', $statusCode);
                $scope->setExtra('gross_amount', $grossAmount);
            });

            Sentry::captureException($e);

            Log::error('MIDTRANS WEBHOOK ERROR', [
                'message' => $e->getMessage(),
                'order_id' => $orderId,
                'transaction_status' => $transactionStatus,
                'payment_type' => $paymentType,
                'transaction_id' => $transactionId,
            ]);

            return response()->json(['message' => 'OK'], 200);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\RefundRequest;
use App\Models\Transaksi;
use App\Notifications\UserStatusPesananDatabaseNotification;
use App\Notifications\UserStatusPesananDiupdate;
use App\Services\Midtrans\MidtransService;
use App\Services\Shipping\TrackingResolver;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransaksiController extends Controller
{
    // Midtrans auto refundable channels
    private const MIDTRANS_REFUNDABLE_TYPES = ['credit_card', 'gopay', 'shopeepay'];

    public function index(Request $request)
    {
        $query = Transaksi::with(['user', 'alamat', 'pembayaran', 'latestRefund'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status_transaksi', $request->status);
        }

        $transaksis = $query->paginate(8)->withQueryString();
        return view('Admin.Transaksi.transaksi', compact('transaksis'));
    }

    public function show($id)
    {
        $transaksi = Transaksi::with([
            'user',
            'alamat',
            'pembayaran',
            'items.produk',
            'items.produkVarian',
            'latestRefund',
            'refunds',
        ])->findOrFail($id);

        return view('Admin.Transaksi.show', compact('transaksi'));
    }

    // =========================
    // STATUS FLOW
    // =========================

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status_transaksi' => 'required|in:pending,menunggu_verifikasi,paid,diproses,dikirim,selesai,dibatalkan,expired,refund,refund_processing,partial_refund',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $trx = Transaksi::with(['items', 'pembayaran'])
                    ->lockForUpdate()
                    ->findOrFail($id);

                $newStatus = (string) $request->status_transaksi;
                $oldStatus = (string) $trx->status_transaksi;

                if ($newStatus === $oldStatus) return;

                // Midtrans PAID hanya via webhook (sesuai rule kamu)
                if ($trx->metode_pembayaran === 'midtrans' && $newStatus === 'paid') {
                    throw new \Exception('Transaksi Midtrans akan PAID otomatis via webhook.');
                }

                $allowed = [
                    'pending'             => ['dibatalkan'],
                    'menunggu_verifikasi' => ['paid', 'dibatalkan'],
                    'paid'                => ['diproses', 'dibatalkan'],
                    'diproses'            => ['dibatalkan', 'dikirim'],
                    'dikirim'             => ['selesai'],
                    'selesai'             => [],
                    'dibatalkan'          => [],
                    'expired'             => [],
                    'refund_processing'   => [],
                    'partial_refund'      => [],
                    'refund'              => [],
                ];

                if (!isset($allowed[$oldStatus]) || !in_array($newStatus, $allowed[$oldStatus], true)) {
                    throw new \Exception("Perubahan status tidak valid dari {$oldStatus} ke {$newStatus}");
                }

                // Cancel -> restore stock
                if ($newStatus === 'dibatalkan') {
                    $this->cancelAndRestoreStock($trx);
                    return;
                }

                // Manual paid (non-midtrans) -> set pembayaran paid + deduct stock
                if ($newStatus === 'paid' && $trx->metode_pembayaran !== 'midtrans') {
                    $trx->paid_at = $trx->paid_at ?? now();

                    if ($trx->pembayaran && $trx->pembayaran->status_pembayaran !== 'paid') {
                        $trx->pembayaran->update([
                            'status_pembayaran'  => 'paid',
                            'tanggal_pembayaran' => now(),
                        ]);
                    }

                    $trx->loadMissing('items');
                    app(StockService::class)->deductWhenPaid($trx);
                }

                $trx->status_transaksi = $newStatus;
                $trx->save();

                // Notify user
                $trx->load('user');
                if ($trx->user) {
                    $trx->user->notify(new UserStatusPesananDatabaseNotification($trx, $oldStatus, $newStatus));
                    $trx->user->notify(new UserStatusPesananDiupdate($trx, $oldStatus, $newStatus));
                }
            });

            return back()->with('flash', [
                'type' => 'success',
                'action' => 'update',
                'title' => 'Status Transaksi Diperbarui',
                'message' => 'Status transaksi berhasil diupdate.',
                'entity' => 'Transaksi',
            ]);
        } catch (\Throwable $e) {
            return back()->with('flash', [
                'type' => 'error',
                'action' => 'validation',
                'title' => 'Update Status Gagal',
                'message' => $e->getMessage(),
                'entity' => 'Transaksi',
            ]);
        }
    }

    /**
     * âœ… KIRIM PESANAN (VALIDASI EKSPEDISI & RESI)
     *
     * Rules:
     * - Ekspedisi harus sesuai pilihan user saat checkout (transaksis.kurir_kode).
     * - Admin TIDAK boleh mengganti kurir.
     * - Resi Dummy-POS- hanya boleh kalau kurir POS.
     */
    public function kirim(Request $request, $id)
    {
        // cukup resi saja yang diinput admin (kurir ikut checkout)
        $request->validate([
            'no_resi' => 'required|string|max:100',
            // 'ekspedisi' boleh kamu biarkan ada di form, tapi akan divalidasi & diabaikan kalau tidak cocok
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $trx = Transaksi::with('user')
                    ->lockForUpdate()
                    ->findOrFail($id);

                if (!in_array($trx->status_transaksi, ['paid', 'diproses'], true)) {
                    throw new \Exception('Transaksi belum siap dikirim.');
                }

                // âœ… ambil kurir dari transaksi (hasil checkout)
                $courier = strtolower(trim((string) ($trx->kurir_kode ?? '')));

                if ($courier === '') {
                    throw new \Exception('Kurir pada transaksi belum tersimpan. Pastikan checkout menyimpan kurir_kode.');
                }

                // âœ… jika admin masih mengirim ekspedisi dari form, cek harus sama
                if ($request->filled('ekspedisi')) {
                    $inputCourier = strtolower(trim((string) $request->ekspedisi));
                    if ($inputCourier !== $courier) {
                        throw new \Exception("Ekspedisi tidak boleh diubah. Kurir transaksi adalah '{$courier}'.");
                    }
                }

                $resi = $this->normalizeDummyResi(
                    trim((string) $request->no_resi),
                    $courier
                );

                // (opsional) kalau POS tapi resi kosong/aneh, tetap valid karena kamu izinkan dummy.
                // (opsional) kalau JNE/J&T/Sicepat dsb, kamu bisa tambah rule minimal panjang resi:
                // if (!$isPosCourier && strlen($resi) < 8) throw new \Exception('Nomor resi terlalu pendek.');

                $oldStatus = (string) $trx->status_transaksi;

                // âœ… ekspedisi yang disimpan harus sesuai pilihan checkout (bukan input admin)
                $trx->update([
                    'status_transaksi' => 'dikirim',
                    'ekspedisi'        => strtoupper($trx->kurir_kode ?? ''), // contoh: JNE, POS, JNT (atau kamu bisa mapping nama tampil)
                    'no_resi'          => $resi,
                    'tanggal_dikirim'  => now(),
                ]);

                if ($trx->user) {
                    $trx->user->notify(new UserStatusPesananDatabaseNotification($trx, $oldStatus, 'dikirim'));
                    $trx->user->notify(new UserStatusPesananDiupdate($trx, $oldStatus, 'dikirim'));
                }
            });

            return back()->with('flash', [
                'type' => 'success',
                'action' => 'update',
                'title' => 'Pesanan Dikirim',
                'message' => 'Pesanan berhasil dikirim.',
                'entity' => 'Transaksi',
            ]);
        } catch (\Throwable $e) {
            return back()->with('flash', [
                'type' => 'error',
                'action' => 'validation',
                'title' => 'Kirim Pesanan Gagal',
                'message' => $e->getMessage(),
                'entity' => 'Transaksi',
            ]);
        }
    }

    // =========================
    // REFUND FLOW
    // =========================

    public function processRefund(Request $request, $id)
    {
        try {
            $result = ['ok' => true, 'message' => 'Refund diproses.'];

            DB::transaction(function () use ($id, &$result) {
                $trx = Transaksi::with(['latestRefund', 'refunds', 'items', 'pembayaran', 'user'])
                    ->lockForUpdate()
                    ->findOrFail($id);

                $refund = $trx->latestRefund;
                if (!$refund) {
                    throw new \Exception('Tidak ada pengajuan refund untuk transaksi ini.');
                }

                if (!in_array($refund->status, [RefundRequest::STATUS_REQUESTED, RefundRequest::STATUS_PROCESSING, RefundRequest::STATUS_FAILED], true)) {
                    throw new \Exception('Refund tidak bisa diproses. Status refund: ' . $refund->status);
                }

                if (!in_array($trx->status_transaksi, ['paid', 'diproses', 'dikirim', 'selesai'], true)) {
                    throw new \Exception('Refund hanya boleh untuk transaksi yang sudah dibayar / diproses / dikirim / selesai.');
                }

                $amount = (int) $refund->amount;
                if ($amount <= 0 || $amount > (int) $trx->total_pembayaran) {
                    throw new \Exception('Amount refund tidak valid.');
                }

                if ($trx->metode_pembayaran === 'midtrans') {
                    $orderId = $trx->midtrans_order_id ?: $trx->kode_transaksi;
                    if (!$orderId) {
                        throw new \Exception('Order ID Midtrans tidak ditemukan pada transaksi.');
                    }

                    $paymentType = (string) ($trx->midtrans_payment_type ?? '');
                    $autoRefundSupported = ($paymentType !== '' && in_array($paymentType, self::MIDTRANS_REFUNDABLE_TYPES, true));

                    if (!$autoRefundSupported) {
                        if ($refund->method !== RefundRequest::METHOD_MANUAL) {
                            $refund->update(['method' => RefundRequest::METHOD_MANUAL]);
                        }

                        if (in_array($refund->status, [RefundRequest::STATUS_REQUESTED, RefundRequest::STATUS_FAILED], true)) {
                            $refund->update([
                                'status' => RefundRequest::STATUS_PROCESSING,
                                'midtrans_response' => [
                                    'note' => $paymentType === ''
                                        ? 'Payment type belum tersimpan. Refund akan diproses manual.'
                                        : "Payment type '{$paymentType}' tidak mendukung refund otomatis. Refund manual diperlukan.",
                                ],
                                'synced_at' => now(),
                            ]);
                        }

                        $trx->update(['status_transaksi' => 'refund_processing']);

                        Pembayaran::updateOrCreate(
                            ['transaksi_id' => $trx->id],
                            [
                                'metode_pembayaran'  => 'midtrans',
                                'total_pembayaran'   => $trx->total_pembayaran,
                                'status_pembayaran'  => 'refund_processing',
                                'tanggal_pembayaran' => $trx->paid_at ?? now(),
                                'bukti_transfer'     => null,
                            ]
                        );

                        $result['ok'] = true;
                        $result['message'] = 'Channel Midtrans ini perlu refund manual. Transfer manual lalu klik Finalize Manual Refund.';
                        return;
                    }

                    if ($refund->status === RefundRequest::STATUS_REQUESTED) {
                        $refund->update(['status' => RefundRequest::STATUS_PROCESSING]);
                    }

                    if ($refund->status === RefundRequest::STATUS_PROCESSING && $refund->midtrans_refund_key) {
                        $trx->update(['status_transaksi' => 'refund_processing']);

                        Pembayaran::updateOrCreate(
                            ['transaksi_id' => $trx->id],
                            [
                                'metode_pembayaran'  => 'midtrans',
                                'total_pembayaran'   => $trx->total_pembayaran,
                                'status_pembayaran'  => 'refund_processing',
                                'tanggal_pembayaran' => $trx->paid_at ?? now(),
                                'bukti_transfer'     => null,
                            ]
                        );

                        $result['ok'] = true;
                        $result['message'] = 'Refund Midtrans sudah pernah dipanggil. Menunggu webhook untuk finalisasi.';
                        return;
                    }

                    $refundKey = $refund->midtrans_refund_key ?: $this->makeRefundKey($trx);

                    /** @var MidtransService $svc */
                    $svc = app(MidtransService::class);
                    $res = $svc->refund($orderId, $refundKey, $amount, $refund->reason ?: 'Refund');

                    $refund->update([
                        'method'              => RefundRequest::METHOD_MIDTRANS,
                        'midtrans_refund_key' => $refundKey,
                        'midtrans_request'    => [
                            'refund_key'    => $refundKey,
                            'amount'        => $amount,
                            'reason'        => $refund->reason ?: 'Refund',
                            'payment_type'  => $paymentType,
                            'order_id'      => $orderId,
                        ],
                        'midtrans_response'   => $res['body'] ?? ['error' => ($res['error'] ?? 'unknown')],
                        'synced_at'           => now(),
                        'status'              => ($res['ok'] ?? false) ? RefundRequest::STATUS_PROCESSING : RefundRequest::STATUS_FAILED,
                    ]);

                    if (!($res['ok'] ?? false)) {
                        Log::warning('MIDTRANS REFUND API FAILED', [
                            'transaksi_id' => $trx->id,
                            'order_id'     => $orderId,
                            'refund_id'    => $refund->id,
                            'payment_type' => $paymentType,
                            'http_status'  => $res['http_status'] ?? null,
                            'body'         => $res['body'] ?? null,
                            'error'        => $res['error'] ?? null,
                        ]);

                        $result['ok'] = false;
                        $result['message'] =
                            (string) (data_get($res, 'body.status_message')
                                ?: ('Midtrans error code: ' . (data_get($res, 'body.status_code') ?? '-'))
                                ?: ($res['error'] ?? 'Refund Midtrans gagal.'));
                        return;
                    }

                    $trx->update(['status_transaksi' => 'refund_processing']);

                    Pembayaran::updateOrCreate(
                        ['transaksi_id' => $trx->id],
                        [
                            'metode_pembayaran'  => 'midtrans',
                            'total_pembayaran'   => $trx->total_pembayaran,
                            'status_pembayaran'  => 'refund_processing',
                            'tanggal_pembayaran' => $trx->paid_at ?? now(),
                            'bukti_transfer'     => null,
                        ]
                    );

                    $result['ok'] = true;
                    $result['message'] = 'Refund Midtrans berhasil dipanggil. Menunggu webhook Midtrans untuk finalisasi.';
                    return;
                }

                // NON-MIDTRANS -> finalize langsung
                $this->restoreStockOnceForRefund($trx, $refund);

                $refund->update([
                    'method'      => RefundRequest::METHOD_MANUAL,
                    'status'      => RefundRequest::STATUS_REFUNDED,
                    'synced_at'   => now(),
                    'refunded_at' => $refund->refunded_at ?? now(),
                ]);

                Pembayaran::updateOrCreate(
                    ['transaksi_id' => $trx->id],
                    [
                        'metode_pembayaran'  => $trx->metode_pembayaran,
                        'total_pembayaran'   => $trx->total_pembayaran,
                        'status_pembayaran'  => 'refund',
                        'tanggal_pembayaran' => $trx->paid_at ?? now(),
                        'bukti_transfer'     => $trx->pembayaran?->bukti_transfer,
                    ]
                );

                $oldStatus = (string) $trx->status_transaksi;
                $trx->update(['status_transaksi' => 'refund']);

                if ($trx->user && $oldStatus !== 'refund') {
                    $trx->user->notify(new UserStatusPesananDatabaseNotification($trx, $oldStatus, 'refund'));
                    $trx->user->notify(new UserStatusPesananDiupdate($trx, $oldStatus, 'refund'));
                }

                $result['ok'] = true;
                $result['message'] = 'Refund manual selesai diproses.';
            });

            return back()->with('flash', [
                'type' => $result['ok'] ? 'success' : 'error',
                'action' => 'update',
                'title' => $result['ok'] ? 'Refund Diproses' : 'Refund Gagal',
                'message' => $result['message'],
                'entity' => 'Refund',
            ]);
        } catch (\Throwable $e) {
            return back()->with('flash', [
                'type' => 'error',
                'action' => 'validation',
                'title' => 'Refund Gagal',
                'message' => $e->getMessage(),
                'entity' => 'Refund',
            ]);
        }
    }

    public function finalizeManualRefund(Request $request, $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $trx = Transaksi::with(['latestRefund', 'items', 'pembayaran', 'user'])
                    ->lockForUpdate()
                    ->findOrFail($id);

                $refund = $trx->latestRefund;
                if (!$refund) {
                    throw new \Exception('Tidak ada pengajuan refund.');
                }

                if (($refund->method ?? '') !== RefundRequest::METHOD_MANUAL) {
                    throw new \Exception('Finalize manual hanya untuk refund method MANUAL.');
                }

                if (!in_array($refund->status, [RefundRequest::STATUS_REQUESTED, RefundRequest::STATUS_PROCESSING, RefundRequest::STATUS_FAILED], true)) {
                    throw new \Exception('Refund tidak bisa difinalisasi. Status: ' . $refund->status);
                }

                $this->restoreStockOnceForRefund($trx, $refund);

                $refund->update([
                    'status'      => RefundRequest::STATUS_REFUNDED,
                    'synced_at'   => now(),
                    'refunded_at' => $refund->refunded_at ?? now(),
                ]);

                Pembayaran::updateOrCreate(
                    ['transaksi_id' => $trx->id],
                    [
                        'metode_pembayaran'  => $trx->metode_pembayaran,
                        'total_pembayaran'   => $trx->total_pembayaran,
                        'status_pembayaran'  => 'refund',
                        'tanggal_pembayaran' => $trx->paid_at ?? now(),
                        'bukti_transfer'     => $trx->pembayaran?->bukti_transfer,
                    ]
                );

                $oldStatus = (string) $trx->status_transaksi;
                $trx->update(['status_transaksi' => 'refund']);

                if ($trx->user && $oldStatus !== 'refund') {
                    $trx->user->notify(new UserStatusPesananDatabaseNotification($trx, $oldStatus, 'refund'));
                    $trx->user->notify(new UserStatusPesananDiupdate($trx, $oldStatus, 'refund'));
                }
            });

            return back()->with('flash', [
                'type' => 'success',
                'action' => 'update',
                'title' => 'Refund Difinalisasi',
                'message' => 'Refund manual berhasil difinalisasi.',
                'entity' => 'Refund',
            ]);
        } catch (\Throwable $e) {
            return back()->with('flash', [
                'type' => 'error',
                'action' => 'validation',
                'title' => 'Finalize Refund Gagal',
                'message' => $e->getMessage(),
                'entity' => 'Refund',
            ]);
        }
    }

    // =========================
    // ADMIN TRACKING JSON
    // =========================

    public function trackingJson(Transaksi $transaksi, TrackingResolver $resolver)
    {
        $transaksi->load(['alamat', 'user']);

        $result = $resolver->resolve($transaksi);
        $tracking = $result['data'] ?? [];

        $timeline = collect(data_get($tracking, 'timeline', []))
            ->map(function ($ev) {
                $raw = data_get($ev, 'datetime');

                $ev['datetime_raw']  = $raw;
                $ev['datetime_wita'] = $raw;
                $ev['datetime_iso']  = null;

                if ($raw) {
                    try {
                        $hasTz = preg_match('/(Z|[+\-]\d{2}:?\d{2})$/', trim((string)$raw)) === 1;

                        $dt = $hasTz
                            ? Carbon::parse($raw)->timezone('Asia/Makassar')
                            : Carbon::parse($raw, 'Asia/Makassar');

                        $ev['datetime_wita'] = $dt->format('Y-m-d H:i');
                        $ev['datetime_iso']  = $dt->toIso8601String();
                    } catch (\Throwable $e) {}
                }

                return $ev;
            })
            ->sortByDesc(fn($ev) => data_get($ev, 'datetime_iso') ?: data_get($ev, 'datetime_wita') ?: data_get($ev, 'datetime_raw'))
            ->values()
            ->all();

        data_set($tracking, 'timeline', $timeline);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'customer' => [
                'name'  => $transaksi->user->name ?? '-',
                'email' => $transaksi->user->email ?? '-',
            ],
            'alamat' => $transaksi->alamat,
            'tracking' => $tracking,
        ], 200);
    }

    // =========================
    // HELPERS
    // =========================

    private function cancelAndRestoreStock(Transaksi $trx): void
    {
        if ($trx->status_transaksi === 'dibatalkan') return;

        $trx->loadMissing('items', 'pembayaran');

        app(StockService::class)->restore($trx, 'Cancel ' . ($trx->kode_transaksi ?? '-'));

        $trx->update(['status_transaksi' => 'dibatalkan']);

        if ($trx->pembayaran) {
            $newPayStatus = $trx->pembayaran->status_pembayaran;
            if ($newPayStatus !== 'paid') $newPayStatus = 'ditolak';
            $trx->pembayaran->update(['status_pembayaran' => $newPayStatus]);
        }
    }

    private function restoreStockOnceForRefund(Transaksi $trx, RefundRequest $refund): void
    {
        if ($refund->stock_restored_at) {
            return;
        }

        $trx->loadMissing('items');

        app(StockService::class)->restore(
            $trx,
            'Refund ' . ($trx->kode_transaksi ?? '-') . ' (#' . ($refund->id ?? '-') . ')'
        );

        $refund->update([
            'stock_restored_at' => now(),
        ]);
    }

    private function makeRefundKey(Transaksi $trx): string
    {
        return 'RF-' .
            ($trx->kode_transaksi ?? $trx->id) . '-' .
            now()->format('YmdHis') . '-' .
            Str::upper(Str::random(4));
    }

    private function normalizeDummyResi(string $resi, string $courier): string
    {
        if ($resi === '') {
            throw new \Exception('Nomor resi wajib diisi.');
        }

        $upperResi = strtoupper($resi);
        $dummyPrefix = $this->dummyPrefixForCourier($courier);

        if ($dummyPrefix === null && str_starts_with($upperResi, 'DUMMY-')) {
            throw new \Exception('Kurir ini tidak mendukung format resi dummy otomatis.');
        }

        if ($dummyPrefix !== null) {
            if (str_starts_with($upperResi, 'DUMMY-') && !str_starts_with($upperResi, $dummyPrefix)) {
                throw new \Exception("Resi dummy untuk kurir ini harus diawali {$dummyPrefix}");
            }

            if ($upperResi === 'DUMMY' || $upperResi === rtrim($dummyPrefix, '-')) {
                return $dummyPrefix . now()->format('YmdHis');
            }
        }

        return $resi;
    }

    private function dummyPrefixForCourier(string $courier): ?string
    {
        return match (strtolower(trim($courier))) {
            'jne' => 'DUMMY-JNE-',
            'jnt', 'j&t', 'j&t express' => 'DUMMY-JNT-',
            'pos', 'posindonesia', 'pos_indonesia' => 'DUMMY-POS-',
            default => null,
        };
    }
}


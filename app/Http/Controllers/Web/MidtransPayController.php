<?php

namespace App\Http\Controllers\Web;

use App\Models\Transaksi;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\Midtrans\MidtransService;
use Sentry\Laravel\Facade as Sentry;

class MidtransPayController extends Controller
{
    public function pay(Transaksi $transaksi, MidtransService $midtrans)
    {
        abort_if($transaksi->user_id !== Auth::id(), 403);

        // pastikan relasi siap dipakai
        $transaksi->loadMissing(['items', 'user', 'alamat']);

        // validasi metode
        if ($transaksi->metode_pembayaran !== 'midtrans') {
            return redirect()->route('transaksi.show', $transaksi->id)
  ->with('flash', [
    'type' => 'error',
    'action' => 'payment',
    'entity' => 'Transaksi',
    'detail' => 'Metode pembayaran transaksi ini bukan Midtrans.',
    'timer' => 3200,
  ]);
        }

        // blok kalau sudah final
        if (in_array($transaksi->status_transaksi, ['paid', 'expired', 'dibatalkan'], true)) {
            return redirect()->route('transaksi.show', $transaksi->id)
  ->with('flash', [
    'type' => 'warning',
    'action' => 'payment',
    'entity' => 'Transaksi',
    'detail' => 'Transaksi tidak bisa dibayar.',
    'timer' => 3200,
  ]);
        }

        /**
         * ✅ RULE UTAMA:
         * - midtrans_order_id SET SEKALI SAJA (kalau kosong).
         * - JANGAN "sync" paksa sama kode_transaksi kalau sudah pernah dipakai,
         *   karena webhook order_id bisa jadi beda (transaksi lama).
         */
        if (empty($transaksi->midtrans_order_id)) {
            $transaksi->midtrans_order_id = $transaksi->kode_transaksi;
            // token lama tidak valid kalau order_id baru saja diisi
            $transaksi->snap_token = null;
        }

        // set deadline 24 jam dari created_at (kalau belum ada)
        if (!$transaksi->payment_deadline) {
            $transaksi->payment_deadline = now('Asia/Makassar')->addHour();
        }

        // kalau expired
        if (now('Asia/Makassar')->greaterThan($transaksi->payment_deadline)) {
            $transaksi->status_transaksi = 'expired';
            $transaksi->save();

           return redirect()->route('transaksi.show', $transaksi->id)
  ->with('flash', [
    'type' => 'warning',
    'action' => 'expired',
    'entity' => 'Transaksi',
    'detail' => 'Transaksi sudah expired.',
    'timer' => 3200,
  ]);
        }

        // simpan perubahan order_id/deadline jika ada
        $transaksi->save();

        // ✅ bikin snap token hanya kalau belum ada
        if (empty($transaksi->snap_token)) {

            $items = $transaksi->items->map(function ($it) {
                return [
                    'id'       => (string) ($it->produk_id ?? $it->id),
                    'price'    => (int) $it->harga_satuan,
                    'quantity' => (int) $it->qty,
                    'name'     => (string) $it->nama_produk,
                ];
            })->values()->all();

            // ongkir masuk ke item_details agar total konsisten
            if (($transaksi->ongkir ?? 0) > 0) {
                $items[] = [
                    'id'       => 'ONGKIR',
                    'price'    => (int) $transaksi->ongkir,
                    'quantity' => 1,
                    'name'     => 'Ongkos Kirim',
                ];
            }

            $params = [
                'transaction_details' => [
                    // ✅ PENTING: pakai midtrans_order_id (bukan kode_transaksi langsung)
                    'order_id'     => (string) $transaksi->midtrans_order_id,
                    'gross_amount' => (int) $transaksi->total_pembayaran,
                ],
                'customer_details' => [
                    'first_name' => $transaksi->user->name ?? 'Customer',
                    'email'      => $transaksi->user->email ?? null,
                    'phone'      => $transaksi->alamat->no_telp ?? null,
                ],
                'item_details' => $items,
                'callbacks' => [
                    'finish' => route('transaksi.show', $transaksi->id),
                ],
            ];

            try {
                $token = $midtrans->getSnapToken($params);
            } catch (\Throwable $e) {
                Sentry::withScope(function (\Sentry\State\Scope $scope) use ($transaksi, $e): void {
                    $scope->setTag('module', 'midtrans');
                    $scope->setTag('feature', 'midtrans.pay');
                    $scope->setTag('midtrans_status', 'snap_token_exception');
                    $scope->setExtra('transaksi_id', $transaksi->id);
                    $scope->setExtra('order_id', $transaksi->midtrans_order_id ?: $transaksi->kode_transaksi);
                    Sentry::captureException($e);
                });

                Log::error('MIDTRANS SNAP TOKEN ERROR', [
                    'transaksi_id' => $transaksi->id,
                    'order_id' => $transaksi->midtrans_order_id ?: $transaksi->kode_transaksi,
                    'message' => $e->getMessage(),
                ]);

                return redirect()->route('transaksi.show', $transaksi->id)
                    ->with('flash', [
                        'type' => 'error',
                        'action' => 'payment',
                        'entity' => 'Transaksi',
                        'detail' => 'Gagal membuat sesi pembayaran Midtrans. Silakan coba lagi.',
                        'timer' => 3500,
                    ]);
            }

            $transaksi->update([
                'snap_token' => $token,
            ]);
        }

        return view('web.Pembayaran.midtrans', [
            'transaksi' => $transaksi,
            'snapToken' => $transaksi->snap_token,
            'clientKey' => $midtrans->clientKey(),
            'snapJsUrl' => $midtrans->snapJsUrl(),
        ]);
    }
}

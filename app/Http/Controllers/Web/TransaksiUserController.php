<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Services\Shipping\TrackingResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class TransaksiUserController extends Controller
{

    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = Transaksi::with(['alamat', 'pembayaran', 'latestRefund'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status_transaksi', $request->status);
        }

        $transaksis = $query->paginate(10)->withQueryString();
        return view('web.Transaksi.transaksi', compact('transaksis'));
    }

    public function show(Transaksi $transaksi)
    {
        abort_if($transaksi->user_id !== Auth::id(), 403);

        $transaksi->load([
            'alamat',
            'items.produk',
            'items.produkVarian',
            'pembayaran',
            'latestRefund'
        ]);

        return view('web.Transaksi.show', compact('transaksi'));
    }

    // ✅ user konfirmasi barang sampai => selesai
    public function diterima(Transaksi $transaksi)
    {
        abort_if($transaksi->user_id !== Auth::id(), 403);

        return DB::transaction(function () use ($transaksi) {
            $locked = Transaksi::whereKey($transaksi->id)->lockForUpdate()->first();

            if ($locked->status_transaksi !== 'dikirim') {
                return back()->with('error', 'Pesanan belum bisa diselesaikan.');
            }

            $locked->update(['status_transaksi' => 'selesai']);
            return back()->with('success', 'Terima kasih! Pesanan ditandai SELESAI.');
        });
    }

    public function trackingJson(Transaksi $transaksi, TrackingResolver $resolver)
    {
        abort_if($transaksi->user_id !== Auth::id(), 403);

        $transaksi->load('alamat');

        $result = $resolver->resolve($transaksi);
        $tracking = $result['data'] ?? [];
        $timeline = collect(data_get($tracking, 'timeline', []))
            ->map(function ($ev) {
                $raw = data_get($ev, 'datetime');

                $ev['datetime_raw'] = $raw;
                $ev['datetime_wita'] = $raw; // fallback
                $ev['datetime_iso']  = null;

                if ($raw) {
                    try {
                        // Jika string ada timezone/offset → parse langsung.
                        // Jika tidak ada timezone → anggap WIB, convert ke WITA.
                        $hasTz = preg_match('/(Z|[+\-]\d{2}:?\d{2})$/', trim((string)$raw)) === 1;

                        $dt = $hasTz
                            ? Carbon::parse($raw)->timezone('Asia/Makassar')
                            : Carbon::parse($raw, 'Asia/Makassar');

                        $ev['datetime_wita'] = $dt->format('Y-m-d H:i');
                        $ev['datetime_iso']  = $dt->toIso8601String();
                    } catch (\Throwable $e) {
                        // fallback
                    }
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
            'resi' => [
                'awb' => $transaksi->no_resi,
                'courier' => $transaksi->kurir_kode,
                'service' => $transaksi->kurir_layanan,
                'tanggal_dikirim' => optional($transaksi->tanggal_dikirim)
                    ? $transaksi->tanggal_dikirim->timezone('Asia/Makassar')->format('Y-m-d H:i')
                    : null,
            ],
            'alamat' => [
                'nama_penerima' => $transaksi->shipping_nama_penerima,
                'no_telp' => $transaksi->shipping_no_telp,
                'alamat_lengkap' => $transaksi->shipping_alamat_lengkap,
                'kelurahan' => $transaksi->shipping_kelurahan,
                'kecamatan' => $transaksi->shipping_kecamatan,
                'kota' => $transaksi->shipping_kota,
                'provinsi' => $transaksi->shipping_provinsi,
                'kode_pos' => $transaksi->shipping_kode_pos,

            ],
            'tracking' => $tracking,
        ], 200);
    }
}

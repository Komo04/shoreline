<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PembayaranUserController extends Controller
{
    public function index()
    {
        $transaksis = Transaksi::with(['alamat', 'pembayaran'])
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('web.Transaksi.transaksi', compact('transaksis'));
    }

    public function show(Transaksi $transaksi)
    {
        abort_if($transaksi->user_id !== Auth::id(), 403);

        $transaksi->load(['alamat', 'items', 'pembayaran']);

        return view('web.Transaksi.show', compact('transaksi'));
    }

    public function create(Transaksi $transaksi)
    {
        abort_if($transaksi->user_id !== Auth::id(), 403);

        // hanya untuk manual
        if (!in_array($transaksi->metode_pembayaran, ['transfer', 'qris'], true)) {
            return redirect()->route('transaksi.show', $transaksi->id)
  ->with('flash', [
    'type' => 'error',
    'action' => 'validation',
    'entity' => 'Pembayaran',
    'detail' => 'Upload bukti hanya untuk metode transfer/qris.',
    'timer' => 3200,
  ]);
        }

        if (in_array($transaksi->status_transaksi, ['dibatalkan', 'expired'], true)) {
            return redirect()->route('transaksi.show', $transaksi->id)
  ->with('flash', [
    'type' => 'warning',
    'action' => 'expired',
    'entity' => 'Transaksi',
    'detail' => 'Transaksi sudah tidak aktif, tidak bisa upload pembayaran.',
    'timer' => 3200,
  ]);
        }

        if ($transaksi->payment_deadline && now()->greaterThan($transaksi->payment_deadline)) {
            $transaksi->update(['status_transaksi' => 'expired']);

            // kalau ada pembayaran, tandai expired juga (opsional)
            if ($transaksi->pembayaran) {
                $transaksi->pembayaran->update(['status_pembayaran' => 'expired']);
            }

            return redirect()->route('transaksi.show', $transaksi->id)
  ->with('flash', [
    'type' => 'warning',
    'action' => 'expired',
    'entity' => 'Transaksi',
    'detail' => 'Transaksi sudah expired, tidak bisa upload bukti pembayaran.',
    'timer' => 3200,
  ]);
        }

        return view('web.Pembayaran.upload', compact('transaksi'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaksi_id'   => 'required|exists:transaksis,id',
            'bukti_transfer' => 'required|image|max:2048',
        ]);

        $transaksi = Transaksi::with('pembayaran')->findOrFail($request->transaksi_id);

        // security
        abort_if($transaksi->user_id !== Auth::id(), 403);

        // hanya manual
        if (!in_array($transaksi->metode_pembayaran, ['transfer', 'qris'], true)) {
            return back()->with('flash', [
  'type' => 'error',
  'action' => 'validation',
  'entity' => 'Pembayaran',
  'detail' => 'Upload bukti hanya untuk metode transfer/qris.',
  'timer' => 3200,
]);
        }

        // blok upload kalau sudah tidak valid
        if (in_array($transaksi->status_transaksi, ['dibatalkan', 'expired'], true)) {
            return back()->with('error', 'Transaksi tidak bisa dibayar karena sudah dibatalkan/expired.');
        }

        if ($transaksi->payment_deadline && now()->greaterThan($transaksi->payment_deadline)) {
            $transaksi->update(['status_transaksi' => 'expired']);

            if ($transaksi->pembayaran) {
                $transaksi->pembayaran->update(['status_pembayaran' => 'expired']);
            }

            return back()->with('error', 'Transaksi sudah expired, tidak bisa upload bukti pembayaran.');
        }

        return DB::transaction(function () use ($request, $transaksi) {

            // hapus bukti lama jika ada
            if ($transaksi->pembayaran && $transaksi->pembayaran->bukti_transfer) {
                Storage::disk('public')->delete($transaksi->pembayaran->bukti_transfer);
            }

            $bukti = $request->file('bukti_transfer')->store('bukti_transfer', 'public');

            // create/update pembayaran manual
            Pembayaran::updateOrCreate(
                ['transaksi_id' => $transaksi->id],
                [
                    'metode_pembayaran'  => $transaksi->metode_pembayaran,
                    'total_pembayaran'   => $transaksi->total_pembayaran,
                    'status_pembayaran'  => 'menunggu_verifikasi',
                    'bukti_transfer'     => $bukti,

                    // ✅ ini yang bikin tidak null
                    'tanggal_pembayaran' => now(),
                ]
            );

            // transaksi jadi menunggu_verifikasi
            $transaksi->update([
                'status_transaksi' => 'menunggu_verifikasi',
            ]);

            return redirect()
                ->route('transaksi.show', $transaksi->id)
               ->with('flash', [
  'type' => 'success',
  'action' => 'update',
  'entity' => 'Pembayaran',
  'detail' => 'Bukti pembayaran berhasil diupload dan menunggu verifikasi admin.',
  'timer' => 3200,
]);
        });
    }
}

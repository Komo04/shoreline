<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaksi;

use App\Services\StockService;

class PembayaranController extends Controller
{
    // LIST VERIFIKASI
    public function index(Request $request)
    {
        $query = Pembayaran::with('transaksi.user')->latest();

        if ($request->search) {
            $query->whereHas('transaksi.user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status) {
            $query->where('status_pembayaran', $request->status);
        }

        $pembayarans = $query->paginate(10)->withQueryString();

        $menunggu = Pembayaran::where('status_pembayaran', 'menunggu_verifikasi')->count();
        $paidToday = Pembayaran::whereDate('updated_at', today())
            ->where('status_pembayaran', 'paid')
            ->count();

        $totalDibatalkan = Transaksi::where('status_transaksi', 'dibatalkan')->count();

        return view('Admin.Pembayaran.pembayaran', compact('pembayarans', 'menunggu', 'paidToday', 'totalDibatalkan'));
    }

    public function show($id)
    {
        $pembayaran = Pembayaran::with([
            'transaksi.user',
            'transaksi.items.produk',
            'transaksi.items.produkVarian',
        ])->findOrFail($id);

        return view('Admin.Pembayaran.show', compact('pembayaran'));
    }


    // KONFIRMASI (ADMIN)
    public function konfirmasi($id)
    {
        $updated = false;

        DB::transaction(function () use ($id, &$updated) {
            $pembayaran = Pembayaran::with('transaksi')
                ->lockForUpdate()
                ->findOrFail($id);

            if ($pembayaran->metode_pembayaran === 'midtrans') return;
            if ($pembayaran->status_pembayaran !== 'menunggu_verifikasi') return;

            $pembayaran->update([
                'status_pembayaran'  => 'paid',
                'tanggal_pembayaran' => $pembayaran->tanggal_pembayaran ?? now(),
            ]);

            $trx = $pembayaran->transaksi;
            $trx->update([
                'status_transaksi' => 'paid',
                'paid_at'          => $trx->paid_at ?? now(),
            ]);

            $trx->loadMissing('items');

            app(StockService::class)->deductWhenPaid($trx);

            $updated = true;
        });

        if (!$updated) {
            return back()->with('flash', [
                'type' => 'error',
                'action' => 'validation',
                'title' => 'Konfirmasi Gagal',
                'message' => 'Pembayaran sudah diproses atau tidak valid untuk dikonfirmasi.',
                'entity' => 'Pembayaran',
            ]);
        }

        return back()->with('flash', [
            'type' => 'success',
            'action' => 'update',
            'title' => 'Pembayaran Dikonfirmasi',
            'message' => 'Pembayaran manual dikonfirmasi. Transaksi menjadi PAID.',
            'entity' => 'Pembayaran',
        ]);
    }


    public function tolak($id)
    {
        DB::transaction(function () use ($id) {
            $pembayaran = Pembayaran::with(['transaksi.items', 'transaksi.pembayaran'])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($pembayaran->status_pembayaran !== 'menunggu_verifikasi') {
                return;
            }

            $transaksi = $pembayaran->transaksi;

            if ($transaksi->status_transaksi === 'dibatalkan') {
                $pembayaran->update(['status_pembayaran' => 'ditolak']);
                return;
            }

            $pembayaran->update(['status_pembayaran' => 'ditolak']);

            $transaksi->loadMissing('items');

            app(StockService::class)->restore(
                $transaksi,
                'Cancel (Tolak Pembayaran) ' . $transaksi->kode_transaksi
            );

            $transaksi->update(['status_transaksi' => 'dibatalkan']);
        });

        return back()->with('flash', [
            'type' => 'success',
            'action' => 'update',
            'title' => 'Pembayaran Ditolak',
            'message' => 'Pembayaran ditolak dan transaksi dibatalkan.',
            'entity' => 'Pembayaran',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LaporanPendapatanController extends Controller
{
    private function baseQuery($start, $end)
    {
        return Transaksi::with('user')
            ->whereIn('status_transaksi', ['paid', 'diproses', 'dikirim', 'selesai'])
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, $end])
            ->orderByDesc('paid_at');
    }

    public function index(Request $request)
    {
        $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        $start = $request->filled('start')
            ? Carbon::parse($request->start)->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $end = $request->filled('end')
            ? Carbon::parse($request->end)->endOfDay()
            : now()->endOfMonth()->endOfDay();

        // ✅ paginate untuk halaman laporan
        $rows = $this->baseQuery($start, $end)
            ->paginate(10)
            ->withQueryString();

        // total pendapatan tetap dihitung dari semua data di periode tsb (bukan hanya 1 halaman)
        $totalPendapatan = $this->baseQuery($start, $end)->sum('total_pembayaran');

        return view('Admin.Laporan.laporan', compact('rows', 'totalPendapatan', 'start', 'end'));
    }

    public function cetak(Request $request)
    {
        $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        $start = $request->filled('start')
            ? Carbon::parse($request->start)->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $end = $request->filled('end')
            ? Carbon::parse($request->end)->endOfDay()
            : now()->endOfMonth()->endOfDay();

        // ✅ cetak ambil semua
        $rows = $this->baseQuery($start, $end)->get();
        $totalPendapatan = $rows->sum('total_pembayaran');

        return view('Admin.Laporan.cetak', compact('rows', 'totalPendapatan', 'start', 'end'));
    }
}

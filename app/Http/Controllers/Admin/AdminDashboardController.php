<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\ProdukVarian;
use App\Models\Ulasan;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $now = now();
        $statusOK = ['paid', 'diproses', 'dikirim', 'selesai'];
        $statusPending = ['pending'];

        $dashboard = Cache::remember('admin_dashboard:v2', now()->addMinutes(2), function () use ($now, $statusOK, $statusPending) {
            $todayStart = $now->copy()->startOfDay();
            $todayEnd = $now->copy()->endOfDay();
            $weekStart = $now->copy()->startOfWeek()->startOfDay();
            $weekEnd = $now->copy()->endOfWeek()->endOfDay();
            $monthStart = $now->copy()->startOfMonth()->startOfDay();
            $monthEnd = $now->copy()->endOfMonth()->endOfDay();
            $chartStart = $now->copy()->subMonths(11)->startOfMonth();

            $paidByMonth = Transaksi::query()
                ->selectRaw('YEAR(paid_at) as year_num, MONTH(paid_at) as month_num, SUM(total_pembayaran) as total')
                ->whereNotNull('paid_at')
                ->whereBetween('paid_at', [$chartStart, $monthEnd])
                ->whereIn('status_transaksi', $statusOK)
                ->groupByRaw('YEAR(paid_at), MONTH(paid_at)')
                ->get()
                ->keyBy(fn ($row) => sprintf('%04d-%02d', (int) $row->year_num, (int) $row->month_num));

            $chartLabels = [];
            $chartSeries = [];

            for ($i = 11; $i >= 0; $i--) {
                $date = $now->copy()->subMonths($i);
                $key = $date->format('Y-m');
                $chartLabels[] = $date->format('M Y');
                $chartSeries[] = (int) data_get($paidByMonth->get($key), 'total', 0);
            }

            return [
                'pendapatanHariIni' => Transaksi::query()
                    ->whereNotNull('paid_at')
                    ->whereBetween('paid_at', [$todayStart, $todayEnd])
                    ->whereIn('status_transaksi', $statusOK)
                    ->sum('total_pembayaran'),
                'pendapatanMingguIni' => Transaksi::query()
                    ->whereNotNull('paid_at')
                    ->whereBetween('paid_at', [$weekStart, $weekEnd])
                    ->whereIn('status_transaksi', $statusOK)
                    ->sum('total_pembayaran'),
                'pendapatanBulanIni' => Transaksi::query()
                    ->whereNotNull('paid_at')
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->whereIn('status_transaksi', $statusOK)
                    ->sum('total_pembayaran'),
                'jumlahTransaksiBulanIni' => Transaksi::query()
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->count(),
                'jumlahProduk' => \App\Models\Produk::count(),
                'jumlahCustomer' => \App\Models\User::where('user_role', 'customer')->count(),
                'statusCounts' => Transaksi::query()
                    ->selectRaw('status_transaksi, COUNT(*) as total')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->groupBy('status_transaksi')
                    ->pluck('total', 'status_transaksi'),
                'transaksiTerbaru' => Transaksi::query()
                    ->latest()
                    ->take(8)
                    ->get(),
                'transaksiPending' => Transaksi::query()
                    ->whereIn('status_transaksi', $statusPending)
                    ->latest()
                    ->take(8)
                    ->get(),
                'stokMenipis' => ProdukVarian::with('produk')
                    ->where('stok', '<=', 5)
                    ->orderBy('stok')
                    ->take(8)
                    ->get(),
                'ulasanTerbaru' => class_exists(Ulasan::class)
                    ? Ulasan::with('produk')->latest()->take(5)->get()
                    : collect(),
                'chartLabels' => $chartLabels,
                'chartSeries' => $chartSeries,
            ];
        });

        return view('Admin.Dashboard.index', $dashboard);
    }
}

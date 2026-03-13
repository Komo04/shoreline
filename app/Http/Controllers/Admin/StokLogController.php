<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StokLog;
use Illuminate\Http\Request;

class StokLogController extends Controller
{
    public function index(Request $request)
    {
        $query = StokLog::with(['varian.produk']);

        // 🔎 Search produk / warna / ukuran (DI-GROUP biar tidak merusak filter lain)
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->whereHas('varian.produk', function ($qq) use ($search) {
                    $qq->where('nama_produk', 'like', "%{$search}%");
                })
                ->orWhereHas('varian', function ($qq) use ($search) {
                    $qq->where('warna', 'like', "%{$search}%")
                       ->orWhere('ukuran', 'like', "%{$search}%");
                })
                // optional: cari dari keterangan juga
                ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        // 📌 Filter tipe IN / OUT
        if ($request->filled('tipe')) {
            $query->where('tipe', strtoupper($request->tipe));
        }

        // 📅 Filter tanggal range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        }

        $logs = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('Admin.Stoklog.stoklog', compact('logs'));
    }
}

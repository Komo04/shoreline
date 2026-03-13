<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Kategori;

class HomeController extends Controller
{
    public function index()
    {
        $newArrivals = Produk::latest()
            ->withCount('ulasans')
            ->withAvg('ulasans', 'rating')
            ->take(8)
            ->get();

        $kategoris = Kategori::with(['produks' => function ($query) {
            $query->whereNotNull('gambar_produk')
                ->latest()
                ->take(1);
        }])
            ->latest()
            ->take(4)
            ->get();

        return view('web.Home.home', compact('newArrivals', 'kategoris'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Produk;

   use App\Services\StockService;
use Illuminate\Http\Request;


class StokinController extends Controller
{
    public function index()
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        // ambil produk + varians (kategori_id dipakai buat filter)
        $produks = Produk::with('varians')->get();

        return view('Admin.Stokin.stokin', compact('produks', 'kategoris'));
    }


public function store(Request $request, StockService $stockService)
{
    $request->validate([
        'varian_id' => 'required|exists:produk_varians,id',
        'jumlah'    => 'required|integer|min:1',
    ]);

    $stockService->manualIn((int)$request->varian_id, (int)$request->jumlah, 'Stok Masuk Manual (Admin)');

    return back()->with('flash', [
        'type' => 'success',
        'action' => 'update',
        'title' => 'Stok Berhasil Ditambahkan',
        'message' => 'Stok berhasil ditambahkan.',
        'entity' => 'Stok',
    ]);
}
}

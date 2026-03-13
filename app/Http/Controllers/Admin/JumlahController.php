<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;

class JumlahController extends Controller
{
    public function index()
    {
        $produks = Produk::with('varians')
            ->withSum('varians as total_stok', 'stok') // bikin kolom total_stok
            ->orderBy('nama_produk')
            ->paginate(5); // ganti 10 sesuai kebutuhan


        return view('Admin.Jumlah.jumlah', compact('produks'));
    }
}

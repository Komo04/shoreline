<?php

namespace App\Http\Controllers\Web;

use App\Models\Produk;
use App\Models\Kategori;
use App\Models\ProdukVarian;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ProdukController extends Controller
{

    public function index(Request $request)
    {
        // ====== Data untuk filter UI ======
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        $minPriceAll = (float) Produk::min('harga');
        $maxPriceAll = (float) Produk::max('harga');

        // ambil warna & ukuran dari varian (yang ada di DB)
        $colors = ProdukVarian::select('warna')->distinct()->orderBy('warna')->pluck('warna');
        $sizes  = ProdukVarian::select('ukuran')->distinct()->orderBy('ukuran')->pluck('ukuran');

        // ====== Query Produk ======
        $query = Produk::query()
            ->with(['varians', 'kategori'])
            ->withAvg('ulasans', 'rating')
            ->withCount('ulasans');

        // search (optional)
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where('nama_produk', 'like', "%{$q}%");
        }

        // kategori (multi)
        if ($request->filled('kategori')) {
            $kategoriIds = array_filter((array) $request->kategori);
            $query->whereIn('kategori_id', $kategoriIds);
        }

        // harga min/max
        $hargaMin = $request->filled('harga_min') ? (float) $request->harga_min : null;
        $hargaMax = $request->filled('harga_max') ? (float) $request->harga_max : null;

        if (!is_null($hargaMin)) $query->where('harga', '>=', $hargaMin);
        if (!is_null($hargaMax)) $query->where('harga', '<=', $hargaMax);

        // warna (multi) -> filter produk yg punya varian warna tersebut
        if ($request->filled('warna')) {
            $warna = array_values(array_filter((array) $request->warna));
            $query->whereHas('varians', function ($q) use ($warna) {
                $q->whereIn('warna', $warna);
            });
        }

        // ukuran (multi)
        if ($request->filled('ukuran')) {
            $ukuran = array_values(array_filter((array) $request->ukuran));
            $query->whereHas('varians', function ($q) use ($ukuran) {
                $q->whereIn('ukuran', $ukuran);
            });
        }

        // hanya yang stok tersedia (opsional)
        if ($request->boolean('in_stock')) {
            $query->whereHas('varians', function ($q) {
                $q->where('stok', '>', 0);
            });
        }

        // sort
        $sort = $request->get('sort', 'terbaru');
        if ($sort === 'harga_asc') {
            $query->orderBy('harga', 'asc');
        } elseif ($sort === 'harga_desc') {
            $query->orderBy('harga', 'desc');
        } elseif ($sort === 'nama_asc') {
            $query->orderBy('nama_produk', 'asc');
        } else {
            $query->latest(); // terbaru
        }

        // paginate + bawa query string filter
        $produks = $query->paginate(9)->appends($request->query());

        return view('web.Produk.produk', compact(
            'produks',
            'kategoris',
            'colors',
            'sizes',
            'minPriceAll',
            'maxPriceAll'
        ));
    }



    public function show(Produk $produk)
    {
        // load relasi yang dipakai di view
        $produk->load([
            'varians',

            'kategori',
            'ulasans.user', // supaya nama user ulasan tampil & tidak N+1
        ]);

        // hitung rating dari tabel ulasans (soft delete otomatis tidak dihitung)
        $ratingAvg   = (float) ($produk->ulasans()->avg('rating') ?? 0);
        $ratingCount = (int) $produk->ulasans()->count();

        // ulasan user yang sedang login (kalau login)
        $userId = Auth::id();

        $myUlasan = $userId
            ? $produk->ulasans()->where('user_id', $userId)->first()
            : null;


        return view('web.Produk.show', compact('produk', 'ratingAvg', 'ratingCount', 'myUlasan'));
    }
}

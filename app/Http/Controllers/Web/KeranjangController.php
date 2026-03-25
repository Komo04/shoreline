<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Keranjang;
use App\Models\ProdukVarian;
use App\Support\ProductSelectionValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class KeranjangController extends Controller
{
    private function forgetCartCountCache(?int $userId): void
    {
        if ($userId) {
            Cache::forget("cart_count:user:{$userId}");
        }
    }

    public function index()
    {
        $keranjangs = Keranjang::with(['produk', 'varian'])
            ->where('user_id', Auth::id())
            ->get();

        $total = $keranjangs->sum(function ($item) {
            $harga = (int) optional($item->produk)->harga;
            $qty   = (int) $item->jumlah_produk;
            return $harga * $qty;
        });

        // ✅ Ambil semua varian untuk produk yang ada di keranjang
        $produkIds = $keranjangs->pluck('produk_id')->unique()->values();
        $varians = ProdukVarian::whereIn('produk_id', $produkIds)
            ->orderBy('warna')
            ->orderBy('ukuran')
            ->get()
            ->groupBy('produk_id');

        // nama variabel ini dipakai view
        $variansByProduk = $varians;

        return view('web.Cart.cart', compact('keranjangs', 'total', 'variansByProduk'));
    }


    public function store(Request $request, ProductSelectionValidator $selectionValidator)
    {
        $request->validate([
            'produk_id' => 'required|exists:produks,id',
            'varian_id' => 'required|exists:produk_varians,id',
            'jumlah_produk' => 'required|integer|min:1',
        ]);

        $userId = Auth::id();
        $validatedSelection = $selectionValidator->validate(
            (int) $request->produk_id,
            (int) $request->varian_id,
            (int) $request->jumlah_produk
        );

        if (! $validatedSelection['ok']) {
            return back()->with('error', $validatedSelection['message']);
        }

        $varian = $validatedSelection['varian'];
        $stok = $validatedSelection['stok'];
        $qtyMasuk = $validatedSelection['qty'];

        $item = Keranjang::where('user_id', $userId)
            ->where('produk_id', $request->produk_id)
            ->where('varian_id', $request->varian_id)
            ->first();

        if ($item) {
            $qtyBaru = (int) $item->jumlah_produk + $qtyMasuk;

            if ($qtyBaru > $stok) {
                return back()->with('error', "Jumlah melebihi stok. Maksimal {$stok}.");
            }

            $item->update(['jumlah_produk' => $qtyBaru]);
            $this->forgetCartCountCache($userId);
            return back()->with('success', 'Qty produk di keranjang berhasil ditambah');
        }

        Keranjang::create([
            'user_id' => $userId,
            'produk_id' => $request->produk_id,
            'varian_id' => $request->varian_id,
            'jumlah_produk' => $qtyMasuk,
        ]);

        $this->forgetCartCountCache($userId);

        return back()->with('success', 'Produk berhasil ditambahkan ke keranjang');
    }

    public function updateVarian(Request $request, $id)
    {
        $request->validate([
            'warna' => 'nullable|string|max:100',
            'ukuran' => 'nullable|string|max:100',
            'warna_current' => 'nullable|string|max:100',
            'ukuran_current' => 'nullable|string|max:100',
        ]);

        $userId = Auth::id();

        $item = Keranjang::with(['produk', 'varian'])
            ->where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // kalau submit hanya salah satu dropdown, pakai yang current untuk lainnya
        $warna = $request->warna ?? $request->warna_current ?? (optional($item->varian)->warna);
        $ukuran = $request->ukuran ?? $request->ukuran_current ?? (optional($item->varian)->ukuran);

        // cari varian yang cocok untuk produk ini
        $varianBaru = ProdukVarian::where('produk_id', $item->produk_id)
            ->when($warna, fn($q) => $q->where('warna', $warna))
            ->when($ukuran, fn($q) => $q->where('ukuran', $ukuran))
            ->first();

        if (!$varianBaru) {
            return back()->with('error', 'Varian tidak ditemukan untuk pilihan tersebut.');
        }

        $stokBaru = (int) $varianBaru->stok;
        if ($stokBaru <= 0) {
            return back()->with('error', 'Stok varian yang dipilih habis.');
        }

        DB::transaction(function () use ($item, $varianBaru, $stokBaru, $userId) {

            $qtySekarang = (int) $item->jumlah_produk;

            // kalau sudah ada item keranjang dengan varian baru -> gabungkan
            $itemSama = Keranjang::where('user_id', $userId)
                ->where('produk_id', $item->produk_id)
                ->where('varian_id', $varianBaru->id)
                ->where('id', '!=', $item->id)
                ->first();

            if ($itemSama) {
                $qtyGabung = (int)$itemSama->jumlah_produk + $qtySekarang;
                if ($qtyGabung > $stokBaru) $qtyGabung = $stokBaru;

                $itemSama->update(['jumlah_produk' => $qtyGabung]);
                $item->delete();
                return;
            }

            // update varian_id
            $item->update(['varian_id' => $varianBaru->id]);

            // kalau qty melebihi stok varian baru -> turunkan
            if ($qtySekarang > $stokBaru) {
                $item->update(['jumlah_produk' => $stokBaru]);
            }
        });

        $this->forgetCartCountCache($userId);

        return back()->with('success', 'Varian berhasil diubah.');
    }



    public function update(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:plus,minus',
        ]);

        $userId = Auth::id();

        $item = Keranjang::with(['varian'])
            ->where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $qty = (int) $item->jumlah_produk;

        if ($request->action === 'plus') {
            if (!$item->varian) {
                return back()->with('error', 'Varian tidak ditemukan.');
            }

            $stok = (int) $item->varian->stok;
            if ($stok <= 0) {
                return back()->with('error', 'Stok habis');
            }

            if (($qty + 1) > $stok) {
                return back()->with('error', 'Jumlah melebihi stok tersedia');
            }

            $item->increment('jumlah_produk');
            $this->forgetCartCountCache($userId);
            return back()->with('success', 'Jumlah produk ditambah');
        }

        // minus
        if ($qty <= 1) {
            $item->delete();
            $this->forgetCartCountCache($userId);
            return back()->with('success', 'Produk dihapus dari keranjang');
        }

        $item->decrement('jumlah_produk');
        $this->forgetCartCountCache($userId);
        return back()->with('success', 'Jumlah produk dikurangi');
    }

    public function destroy($id)
    {
        $userId = Auth::id();

        $item = Keranjang::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $item->delete();
        $this->forgetCartCountCache($userId);

        return back()->with('success', 'Produk dihapus dari keranjang');
    }
}

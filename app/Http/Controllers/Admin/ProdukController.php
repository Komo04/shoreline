<?php

namespace App\Http\Controllers\Admin;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $query = Produk::with('kategori');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_produk', 'like', "%{$search}%")
                  ->orWhere('deskripsi_produk', 'like', "%{$search}%");
            });
        }

        // Filter kategori
        if ($request->filled('kategori')) {
            $query->where('kategori_id', $request->kategori);
        }

        $produks = $query->latest()->paginate(10)->withQueryString();
        $kategoris = Kategori::all();

        return view('Admin.Produk.produk', compact('produks', 'kategoris'));
    }

    public function create()
    {
        $kategoris = Kategori::all();
        return view('Admin.Produk.create', compact('kategoris'));
    }

    public function show(Produk $produk)
    {
        $produk->load([
            'kategori',
            'varians' => fn ($query) => $query->orderBy('warna')->orderBy('ukuran'),
        ]);

        return view('Admin.Produk.show', [
            'produk' => $produk,
            'totalStok' => $produk->varians->sum('stok'),
            'totalVarian' => $produk->varians->count(),
        ]);
    }

    public function store(Request $request)
    {
        // ✅ rapikan spasi + siapkan versi norm (lowercase)
        $nama = (string) Str::of($request->nama_produk)->trim()->squish();
        $request->merge([
            'nama_produk' => $nama,
            'nama_produk_norm' => Str::lower($nama),
        ]);

        $validatedData = $request->validate([
            'nama_produk'      => 'required|string|max:255',
            'nama_produk_norm' => [
                'required',
                'string',
                'max:255',
                Rule::unique('produks', 'nama_produk_norm'),
            ],
            'deskripsi_produk' => 'required|string',
            'harga'            => ['required', 'regex:/^[0-9.]+$/'], // input boleh pakai titik ribuan
            'kategori_id'      => 'required|exists:kategoris,id',
            'keterangan'       => 'required|string',
            'gambar_produk'    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'nama_produk_norm.unique' => 'Nama produk sudah ada (huruf besar/kecil dianggap sama).',
        ]);

        // Simpan harga sebagai integer (hapus semua non-digit)
        $validatedData['harga'] = (int) preg_replace('/\D/', '', (string) $request->harga);

        // Upload gambar
        if ($request->hasFile('gambar_produk')) {
            $validatedData['gambar_produk'] = $request->file('gambar_produk')->store('produk', 'public');
        }

        Produk::create($validatedData);

        return redirect()->route('admin.produk.index')
  ->with('flash', ['type'=>'success','action'=>'create','entity'=>'Produk']);
    }

    public function edit(Produk $produk)
    {
        $kategoris = Kategori::all();
        return view('Admin.Produk.edit', compact('produk', 'kategoris'));
    }

    public function update(Request $request, Produk $produk)
    {
        $nama = (string) Str::of($request->nama_produk)->trim()->squish();
        $request->merge([
            'nama_produk' => $nama,
            'nama_produk_norm' => Str::lower($nama),
        ]);

        $validatedData = $request->validate([
            'nama_produk'      => 'required|string|max:255',
            'nama_produk_norm' => [
                'required',
                'string',
                'max:255',
                Rule::unique('produks', 'nama_produk_norm')->ignore($produk->id),
            ],
            'deskripsi_produk' => 'required|string',
            'harga'            => ['required', 'regex:/^[0-9.]+$/'],
            'kategori_id'      => 'required|exists:kategoris,id',
            'keterangan'       => 'required|string',
            'gambar_produk'    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'nama_produk_norm.unique' => 'Nama produk sudah ada (huruf besar/kecil dianggap sama).',
        ]);

        $validatedData['harga'] = (int) preg_replace('/\D/', '', (string) $request->harga);

        if ($request->hasFile('gambar_produk')) {
            if ($produk->gambar_produk && Storage::disk('public')->exists($produk->gambar_produk)) {
                Storage::disk('public')->delete($produk->gambar_produk);
            }
            $validatedData['gambar_produk'] = $request->file('gambar_produk')->store('produk', 'public');
        }

        $produk->update($validatedData);

      return redirect()->route('admin.produk.index')
  ->with('flash', ['type'=>'success','action'=>'update','entity'=>'Produk']);
    }

    public function destroy(Produk $produk)
    {
        if ($produk->gambar_produk && Storage::disk('public')->exists($produk->gambar_produk)) {
            Storage::disk('public')->delete($produk->gambar_produk);
        }

        $produk->delete();

        return redirect()->route('admin.produk.index')
  ->with('flash', ['type'=>'success','action'=>'delete','entity'=>'Produk']);
    }
}

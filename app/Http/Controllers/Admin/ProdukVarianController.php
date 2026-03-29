<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Keranjang;
use App\Models\Produk;
use App\Models\ProdukVarian;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProdukVarianController extends Controller
{
    private function ukuranOrderSql(): string
    {
        return "CASE ukuran
            WHEN 'XS' THEN 1
            WHEN 'S' THEN 2
            WHEN 'M' THEN 3
            WHEN 'L' THEN 4
            WHEN 'XL' THEN 5
            WHEN 'One Size' THEN 6
            ELSE 99
        END";
    }

    public function index(Request $request, $produk_id)
    {
        $produk = Produk::findOrFail($produk_id);

        $query = $produk->varians();

        // Search warna / ukuran
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('warna', 'like', "%{$search}%")
                    ->orWhere('ukuran', 'like', "%{$search}%");
            });
        }

        // Filter stok
        if ($request->stok === 'available') {
            $query->where('stok', '>', 0);
        } elseif ($request->stok === 'empty') {
            $query->where('stok', '<=', 0);
        }

        $varians = $query
            ->orderBy('warna')
            ->orderByRaw($this->ukuranOrderSql())
            ->paginate(10)
            ->withQueryString();

        return view('Admin.Varian.varian', compact('produk', 'varians'));
    }


    public function create($produk_id)
    {
        $produk = Produk::findOrFail($produk_id);
        return view('Admin.Varian.create', compact('produk'));
    }

    public function store(Request $request, $produk_id)
    {
        $request->validate([
            'warna' => 'required|string|max:50',
            'gambar_varian' => 'nullable|image|max:2048',
            'berat_gram' => 'required|integer|min:1',
            // sizes adalah array berisi size yang dicentang + stoknya
            'sizes' => 'required|array',
        ], [
            'warna.required' => 'Warna wajib diisi.',
            'berat_gram.required' => 'Berat wajib diisi.',
            'berat_gram.integer' => 'Berat harus berupa angka.',
            'berat_gram.min' => 'Berat minimal 1 gram.',
            'sizes.required' => 'Pilih minimal 1 ukuran.',
            'sizes.array' => 'Format ukuran tidak valid.',
            'gambar_varian.image' => 'File gambar varian harus berupa gambar.',
            'gambar_varian.max' => 'Ukuran gambar varian maksimal 2MB.',
        ]);

        // Ambil size yang dicentang
        $selected = collect($request->input('sizes', []))
            ->filter(fn($v) => isset($v['enabled']));

        if ($selected->isEmpty()) {
            return back()->withErrors(['sizes' => 'Pilih minimal 1 ukuran.'])->withInput();
        }

        // Validasi stok per size (harus angka >= 0)
        foreach ($selected as $size => $payload) {
            $stok = $payload['stok'] ?? null;
            if ($stok === null || $stok === '' || !is_numeric($stok) || (int)$stok < 0) {
                return back()->withErrors([
                    "sizes.$size.stok" => "Stok untuk ukuran {$size} wajib angka dan minimal 0."
                ])->withInput();
            }
        }

        $dataBase = [
            'produk_id' => $produk_id,
            'warna' => $request->warna,
            'berat_gram' => (int) $request->berat_gram,
        ];

        if ($request->hasFile('gambar_varian')) {
            $dataBase['gambar_varian'] = $request->file('gambar_varian')->store('varian', 'public');
        }

        $created = 0;
        $skipped = [];

        foreach ($selected as $size => $payload) {
            $stok = (int) $payload['stok'];
            $varian = ProdukVarian::firstOrCreate(
                [
                    'produk_id' => $produk_id,
                    'warna' => $request->warna,
                    'ukuran' => $size,
                ],
                $dataBase + [
                    'ukuran' => $size,
                    'stok' => $stok,
                ]
            );

            if (! $varian->wasRecentlyCreated) {
                $skipped[] = $size;
                continue;
            }

            $created++;
        }

        $msg = "Berhasil tambah {$created} varian.";
        if (!empty($skipped)) {
            $msg .= " Dilewati (sudah ada): " . implode(', ', $skipped);
        }

        return redirect()
            ->route('admin.varian.index', $produk_id)
            ->with('flash', [
                'type' => 'success',
                'message' => $msg,   // override teks generator
                'mode' => 'toast',
                'timer' => 2200,
            ]);
    }


    public function edit(ProdukVarian $varian)
    {
        return view('Admin.Varian.edit', compact('varian'));
    }

    public function update(Request $request, ProdukVarian $varian)
    {
        $request->validate([
            'warna' => 'required|string|max:50',

            // ✅ cegah dobel saat update (ignore id sendiri)
            'ukuran' => [
                'required',
                'string',
                'max:20',
                Rule::unique('produk_varians', 'ukuran')
                    ->ignore($varian->id)
                    ->where(function ($q) use ($varian, $request) {
                        return $q->where('produk_id', $varian->produk_id)
                            ->where('warna', $request->warna);
                    }),
            ],

            'stok' => 'required|integer|min:0',
            'berat_gram' => 'required|integer|min:1',
            'gambar_varian' => 'nullable|image|max:2048',
        ], [
            'ukuran.unique' => 'Varian dengan warna dan ukuran ini sudah ada untuk produk ini.',
        ]);

        $data = $request->only(['warna', 'ukuran', 'stok', 'berat_gram']);

        if ($request->hasFile('gambar_varian')) {
            $data['gambar_varian'] = $request->file('gambar_varian')->store('varian', 'public');
        }

        $varian->update($data);

        return redirect()
            ->route('admin.varian.index', $varian->produk_id)
            ->with('flash', [
                'type' => 'success',
                'action' => 'update',
                'entity' => 'Varian',
            ]);
    }

    public function destroy(ProdukVarian $varian)
    {
        // ✅ hapus item keranjang yang pakai varian ini (biar user tidak error checkout)
        Keranjang::where('varian_id', $varian->id)->delete();

        $varian->delete();

       return back()->with('flash', [
  'type' => 'success',
  'action' => 'delete',
  'entity' => 'Varian',
]);
    }
}

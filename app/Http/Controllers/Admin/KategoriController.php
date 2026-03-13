<?php

namespace App\Http\Controllers\Admin;

use App\Models\Kategori;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class KategoriController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $kategoris = Kategori::when($search, function ($query) use ($search) {
            $query->where('nama_kategori', 'like', '%' . $search . '%');
        })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('Admin.Kategori.kategori', compact('kategoris', 'search'));
    }

    public function create()
    {
        return view('Admin.Kategori.create');
    }

    public function store(Request $request)
    {
        // ✅ rapikan spasi + siapkan versi norm (lowercase)
        $nama = (string) Str::of($request->nama_kategori)->trim()->squish();
        $request->merge([
            'nama_kategori' => $nama,
            'nama_kategori_norm' => Str::lower($nama),
        ]);

        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255',
            'nama_kategori_norm' => [
                'required',
                'string',
                'max:255',
                Rule::unique('kategoris', 'nama_kategori_norm'),
            ],
            'gambar' => 'required|image|max:2048',
        ], [
            'nama_kategori_norm.unique' => 'Nama kategori sudah ada (huruf besar/kecil dianggap sama).',
        ]);

        $validated['gambar'] = $request->file('gambar')->store('kategori', 'public');

        Kategori::create($validated);

        return redirect()->route('admin.kategori.index')
            ->with('flash', ['type' => 'success', 'action' => 'create', 'entity' => 'Kategori']);
    }

    public function edit(Kategori $kategori)
    {
        return view('Admin.Kategori.edit', compact('kategori'));
    }

    public function update(Request $request, Kategori $kategori)
    {
        $nama = (string) Str::of($request->nama_kategori)->trim()->squish();
        $request->merge([
            'nama_kategori' => $nama,
            'nama_kategori_norm' => Str::lower($nama),
        ]);

        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255',
            'nama_kategori_norm' => [
                'required',
                'string',
                'max:255',
                Rule::unique('kategoris', 'nama_kategori_norm')->ignore($kategori->id),
            ],
            'gambar' => 'nullable|image|max:2048',
        ], [
            'nama_kategori_norm.unique' => 'Nama kategori sudah ada (huruf besar/kecil dianggap sama).',
        ]);

        if ($request->hasFile('gambar')) {
            if ($kategori->gambar && Storage::disk('public')->exists($kategori->gambar)) {
                Storage::disk('public')->delete($kategori->gambar);
            }
            $validated['gambar'] = $request->file('gambar')->store('kategori', 'public');
        }

        $kategori->update($validated);

        return redirect()->route('admin.kategori.index')
            ->with('flash', ['type' => 'success', 'action' => 'update', 'entity' => 'Kategori']);
    }

    public function destroy(Kategori $kategori)
    {
        if ($kategori->gambar && Storage::disk('public')->exists($kategori->gambar)) {
            Storage::disk('public')->delete($kategori->gambar);
        }

        $kategori->delete();

       return redirect()->route('admin.kategori.index')
  ->with('flash', ['type'=>'success','action'=>'delete','entity'=>'Kategori']);
    }
}

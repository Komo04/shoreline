<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ulasan;
use Illuminate\Http\Request;

class UlasanAdminController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');

        $ulasans = Ulasan::with(['user', 'produk'])
            ->when($q, function ($query) use ($q) {
                $query->where('komentar', 'like', "%{$q}%")
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('produk', fn($p) => $p->where('nama_produk', 'like', "%{$q}%"));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('Admin.Ulasan.ulasan', compact('ulasans', 'q'));
    }

    public function trash(Request $request)
    {
        $q = $request->query('q');

        $ulasans = Ulasan::onlyTrashed()
            ->with(['user', 'produk'])
            ->when($q, function ($query) use ($q) {
                $query->where('komentar', 'like', "%{$q}%")
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('produk', fn($p) => $p->where('nama_produk', 'like', "%{$q}%"));
            })
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return view('Admin.Ulasan.trash', compact('ulasans', 'q'));
    }

    public function destroy(Ulasan $ulasan)
    {
        $ulasan->delete();
        return back()->with('flash', [
            'type' => 'success',
            'action' => 'delete',
            'title' => 'Ulasan Dipindahkan',
            'message' => 'Ulasan dipindahkan ke Trash.',
            'entity' => 'Ulasan',
        ]);
    }

    public function restore($id)
    {
        $ulasan = Ulasan::onlyTrashed()->findOrFail($id);
        $ulasan->restore();

        return back()->with('flash', [
            'type' => 'success',
            'action' => 'update',
            'title' => 'Ulasan Direstore',
            'message' => 'Ulasan berhasil direstore.',
            'entity' => 'Ulasan',
        ]);
    }

    public function forceDelete($id)
    {
        $ulasan = Ulasan::onlyTrashed()->findOrFail($id);
        $ulasan->forceDelete();

        return back()->with('flash', [
            'type' => 'success',
            'action' => 'delete',
            'title' => 'Ulasan Dihapus Permanen',
            'message' => 'Ulasan dihapus permanen.',
            'entity' => 'Ulasan',
        ]);
    }
}

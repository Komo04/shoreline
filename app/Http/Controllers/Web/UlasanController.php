<?php

namespace App\Http\Controllers\Web;

use App\Models\Produk;
use App\Models\Ulasan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use App\Notifications\AdminUlasanBaru;

class UlasanController extends Controller
{
    public function storeOrUpdate(Request $request, Produk $produk)
    {
        $data = $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'komentar' => 'nullable|string',
        ]);

        Ulasan::updateOrCreate(
            ['user_id' => Auth::id(), 'produk_id' => $produk->id],
            ['rating' => $data['rating'], 'komentar' => $data['komentar'] ?? null]
        );

        $admins = User::where('user_role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new AdminUlasanBaru(
                $produk->id,
                $produk->nama_produk ?? $produk->nama ?? 'Produk',
                Auth::id(),
                Auth::user()->name ?? Auth::user()->nama ?? 'User',
                (int) $data['rating']
            ));
        }

        return back()->with('success', 'Ulasan berhasil disimpan.');
    }
}

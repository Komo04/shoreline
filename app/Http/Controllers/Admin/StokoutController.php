<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Produk;

class StokoutController extends Controller
{
    public function index(Request $request)
    {
        $limit = 5;

        $produks = Produk::with(['varians' => function ($q) use ($limit) {
            $q->where('stok', '<=', $limit);
        }])
            ->whereHas('varians', function ($q) use ($limit) {
                $q->where('stok', '<=', $limit);
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('Admin.Stokout.stokout', compact('produks', 'limit'));
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PurchasedMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // route model binding: /produk/{produk}/ulasan
        $produkParam = $request->route('produk');
        $produkId = is_object($produkParam)
            ? ($produkParam->id ?? null)
            : (is_numeric($produkParam) ? (int) $produkParam : null);

        if (!$produkId) {
            abort(404);
        }

        // Cek apakah user pernah membeli produk tsb dari transaksi + transaksi_items
        $hasPurchased = DB::table('transaksi_items')
            ->join('transaksis', 'transaksis.id', '=', 'transaksi_items.transaksi_id')
            ->where('transaksis.user_id', Auth::id())
            ->where('transaksi_items.produk_id', $produkId)
            // pilih status yang dianggap "sudah sah beli"
            ->whereIn('transaksis.status_transaksi', ['paid', 'dikirim', 'selesai'])
            ->exists();

        if (!$hasPurchased) {
           return redirect()->back()->with('flash', [
  'type' => 'warning',
  'action' => 'forbidden',
  'entity' => 'Ulasan',
  'detail' => 'Kamu hanya bisa memberi ulasan jika sudah membeli produk ini.',
  'timer' => 3200,
]);
        }

        return $next($request);
    }
}

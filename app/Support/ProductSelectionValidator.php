<?php

namespace App\Support;

use App\Models\ProdukVarian;

class ProductSelectionValidator
{
    public function validate(int $produkId, int $varianId, int $qty): array
    {
        $varian = ProdukVarian::with('produk')
            ->where('id', $varianId)
            ->where('produk_id', $produkId)
            ->first();

        if (! $varian || ! $varian->produk) {
            return [
                'ok' => false,
                'message' => 'Varian tidak valid untuk produk ini.',
            ];
        }

        $stok = (int) $varian->stok;

        if ($stok <= 0) {
            return [
                'ok' => false,
                'message' => 'Stok habis untuk varian ini.',
            ];
        }

        if ($qty > $stok) {
            return [
                'ok' => false,
                'message' => "Jumlah melebihi stok. Maksimal {$stok}.",
            ];
        }

        return [
            'ok' => true,
            'varian' => $varian,
            'stok' => $stok,
            'qty' => $qty,
        ];
    }
}

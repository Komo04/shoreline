<?php

namespace App\Observers;

use App\Models\ProdukVarian;
use App\Models\StokLog;

class ProdukVarianObserver
{
    public function created(ProdukVarian $varian): void
    {
        $stokAwal = (int) $varian->stok;

        if ($stokAwal <= 0) {
            return;
        }

        StokLog::create([
            'produk_varian_id' => $varian->id,
            'tipe' => 'IN',
            'jumlah' => $stokAwal,
            'stok_sebelum' => 0,
            'stok_sesudah' => $stokAwal,
            'keterangan' => 'INIT',
        ]);
    }
}

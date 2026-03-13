<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailTransaksi extends Model
{
    protected $table = 'detail_transaksis';

    protected $fillable = [
        'transaksi_id',
        'produk_id',
        'produk_varian_id',
        'jumlah_produk',
        'harga_satuan',
        'subtotal',
    ];

    /* ================= RELATIONS ================= */

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function varian()
    {
        return $this->belongsTo(ProdukVarian::class, 'produk_varian_id');
    }
}

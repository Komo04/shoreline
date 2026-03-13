<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiItem extends Model
{
    protected $fillable = [
        'transaksi_id',
        'produk_id',
        'produk_varian_id',
        'nama_produk',
        'warna',
        'ukuran',
        'qty',
        'harga_satuan',
        'subtotal'
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function produkVarian()
    {
        return $this->belongsTo(ProdukVarian::class, 'produk_varian_id');
    }
}

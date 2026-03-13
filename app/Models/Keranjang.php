<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keranjang extends Model
{
    protected $fillable = [
        'user_id',
        'produk_id',
        'varian_id',
        'jumlah_produk',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function varian()
    {
        return $this->belongsTo(ProdukVarian::class, 'varian_id');
    }
}

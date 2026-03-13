<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokLog extends Model
{
    protected $table = 'stok_logs'; // WAJIB
    protected $fillable = [
        'produk_varian_id',
        'tipe',
        'jumlah',
        'stok_sebelum',
        'stok_sesudah',
        'keterangan'
    ];

    public function varian()
    {
        return $this->belongsTo(ProdukVarian::class, 'produk_varian_id');
    }
}

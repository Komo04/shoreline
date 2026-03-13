<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'produks';

    protected $fillable = [
        'nama_produk',
        'deskripsi_produk',
        'harga',
        'keterangan',
        'kategori_id',
        'gambar_produk',
        'nama_produk_norm',
    ];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function getHargaFormatAttribute()
    {
        return number_format($this->harga, 0, ',', '.');
    }

    public function varians()
    {
        return $this->hasMany(ProdukVarian::class);
    }

    public function keranjangs()
    {
        return $this->hasMany(Keranjang::class);
    }

    public function ulasans()
    {
        return $this->hasMany(Ulasan::class);
    }
}

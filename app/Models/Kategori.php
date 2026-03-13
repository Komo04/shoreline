<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategoris';

    protected $fillable = [
        'nama_kategori',
        'gambar',
        'nama_kategori_norm',
        
    ];
     public function produks()
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }

}



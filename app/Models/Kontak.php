<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kontak extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'email',
        'subjek',
        'pesan',
        'dibaca_pada',
        'dibalas_pada',
        'balasan_subjek',
    ];

    protected $casts = [
        'dibaca_pada' => 'datetime',
        'dibalas_pada' => 'datetime',
    ];
}

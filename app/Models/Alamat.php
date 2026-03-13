<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alamat extends Model
{

    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama_penerima',
        'nama_pengirim',
        'no_telp',
        'kota',
        'kecamatan',
        'kelurahan',
        'provinsi',
        'kode_pos',
        'alamat_lengkap',
        'is_default',
        'destination_id',

    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $fillable = [
        'transaksi_id',
        'status_pembayaran',
        'metode_pembayaran',
        'total_pembayaran',
        'tanggal_pembayaran',
        'bukti_transfer',
    ];

    protected $casts = [
        'tanggal_pembayaran' => 'datetime',
        'total_pembayaran' => 'decimal:2',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }
    
}

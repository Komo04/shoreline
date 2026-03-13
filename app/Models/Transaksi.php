<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $fillable = [
        'kode_transaksi',
        'user_id',
        'alamat_id',
        'metode_pembayaran',
        'status_transaksi',
        'total_pembayaran',
        'payment_deadline',
        'no_resi',
        'ekspedisi',
        'ongkir',
        'kurir_kode',
        'tanggal_dikirim',
        'kurir_layanan',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'midtrans_payment_type',
        'snap_token',
        'created_at',
        'updated_at',
        'paid_at',
        'kurir_etd',
        'kurir_etd_is_business_days',
        'stock_deducted',
        'stock_deducted_at',
        'shipping_nama_penerima',
        'shipping_nama_pengirim',
        'shipping_no_telp',
        'shipping_kota',
        'shipping_kecamatan',
        'shipping_kelurahan',
        'shipping_provinsi',
        'shipping_kode_pos',
        'shipping_alamat_lengkap',
        'shipping_destination_id',

    ];

    protected $casts = [
        'shipping_destination_id' => 'string',
        'payment_deadline' => 'datetime',
        'tanggal_dikirim'  => 'datetime',
        'paid_at'          => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'ongkir'           => 'integer',
        'total_pembayaran' => 'integer',
        'stock_deducted' => 'boolean',
        'stock_deducted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function alamat()
    {
        return $this->belongsTo(Alamat::class);
    }

    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class);
    }

    public function items()
    {
        return $this->hasMany(TransaksiItem::class);
    }
    public function refunds()
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function latestRefund()
    {
        return $this->hasOne(RefundRequest::class)->latestOfMany();
    }
}

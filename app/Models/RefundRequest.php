<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    // (opsional) biar konsisten, minim typo
    public const METHOD_MANUAL   = 'manual';
    public const METHOD_MIDTRANS = 'midtrans';

    public const STATUS_REQUESTED  = 'requested';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_REFUNDED   = 'refunded';
    public const STATUS_FAILED     = 'failed';

    protected $fillable = [
        'transaksi_id',
        'user_id',
        'method',
        'status',
        'amount',
        'reason',
        'bank_name',
        'account_number',
        'account_name',
        'midtrans_response',
        'midtrans_refund_key',
        'midtrans_request',
        'synced_at',
        'proof_path',

        // ✅ baru (idempotency)
        'stock_restored_at',
        'refunded_at',
    ];

    protected $casts = [
        'midtrans_response'  => 'array',
        'midtrans_request'   => 'array',
        'synced_at'          => 'datetime',

        // ✅ baru
        'stock_restored_at'  => 'datetime',
        'refunded_at'        => 'datetime',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // (opsional) helper kecil
    public function isFinal(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }
}

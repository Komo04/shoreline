<?php

namespace App\Notifications;

use App\Models\RefundRequest;
use App\Models\Transaksi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminRefundRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RefundRequest $refund,
        public Transaksi $trx,
        public string $refundType // manual | midtrans | midtrans_failed
    ) {
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $label = match ($this->refundType) {
            'manual' => 'Refund Manual',
            'midtrans' => 'Refund Midtrans',
            'midtrans_failed' => 'Refund Midtrans Gagal',
            default => 'Refund',
        };

        $message = match ($this->refundType) {
            'manual' => "User mengajukan refund manual untuk {$this->trx->kode_transaksi}.",
            'midtrans' => "Refund via Midtrans diproses untuk {$this->trx->kode_transaksi}.",
            'midtrans_failed' => "Refund via Midtrans gagal untuk {$this->trx->kode_transaksi}.",
            default => "Refund untuk {$this->trx->kode_transaksi}.",
        };

        return [
            'type' => 'admin_refund',
            'refund_type' => $this->refundType,
            'label' => $label,
            'message' => $message,
            'transaksi_id' => $this->trx->id,
            'refund_id' => $this->refund->id,
            'amount' => $this->refund->amount,

            // manual info
            'bank_name' => $this->refund->bank_name,
            'account_number' => $this->refund->account_number,
            'account_name' => $this->refund->account_name,
        ];
    }
}

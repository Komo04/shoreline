<?php

namespace App\Notifications;

use App\Models\RefundRequest;
use App\Models\Transaksi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminRefundRequestedMidtrans extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RefundRequest $refund, public Transaksi $trx)
    {
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'refund_midtrans',
            'title' => 'Refund Midtrans Diajukan',
            'message' => 'Refund midtrans untuk transaksi ' . $this->trx->kode_transaksi,
            'transaksi_id' => $this->trx->id,
            'kode_transaksi' => $this->trx->kode_transaksi,
            'refund_request_id' => $this->refund->id,
            'amount' => (int)$this->refund->amount,
            'midtrans_order_id' => $this->trx->midtrans_order_id,
            'midtrans_payment_type' => $this->trx->midtrans_payment_type,
        ];
    }
}

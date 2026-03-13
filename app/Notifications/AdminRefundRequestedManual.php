<?php

namespace App\Notifications;

use App\Models\RefundRequest;
use App\Models\Transaksi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminRefundRequestedManual extends Notification implements ShouldQueue
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
            'type' => 'refund_manual',
            'title' => 'Refund Manual Diajukan',
            'message' => 'Refund manual untuk transaksi ' . $this->trx->kode_transaksi,
            'transaksi_id' => $this->trx->id,
            'kode_transaksi' => $this->trx->kode_transaksi,
            'refund_request_id' => $this->refund->id,
            'amount' => (int)$this->refund->amount,
            'bank_name' => $this->refund->bank_name,
            'account_number' => $this->refund->account_number,
            'account_name' => $this->refund->account_name,
        ];
    }
}

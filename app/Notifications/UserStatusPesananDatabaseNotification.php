<?php

namespace App\Notifications;

use App\Models\Transaksi;
use Illuminate\Notifications\Notification;

class UserStatusPesananDatabaseNotification extends Notification
{
    public function __construct(
        public Transaksi $trx,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'user_status_update',
            'transaksi_id' => $this->trx->id,
            'kode_transaksi' => $this->trx->kode_transaksi,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => "Status pesanan {$this->trx->kode_transaksi} berubah dari {$this->oldStatus} ke {$this->newStatus}",
        ];
    }
}

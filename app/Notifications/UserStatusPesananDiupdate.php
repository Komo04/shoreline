<?php

namespace App\Notifications;

use App\Models\Transaksi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserStatusPesananDiupdate extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transaksi $trx,
        public string $oldStatus,
        public string $newStatus
    ) {
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Update Status Pesanan: {$this->trx->kode_transaksi}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Status pesanan kamu berubah.")
            ->line("Kode Transaksi: {$this->trx->kode_transaksi}")
            ->line("Dari: {$this->oldStatus}")
            ->line("Menjadi: {$this->newStatus}")
            ->action('Lihat Detail Pesanan', route('transaksi.show', $this->trx->id))
            ->line('Terima kasih sudah berbelanja di Shoreline.');
    }

    /**
     * Compatibility fallback for queued jobs created before this notification
     * was split into separate database + mail notifications.
     */
    public function toDatabase($notifiable): array
    {
        return $this->toArray($notifiable);
    }

    public function toArray($notifiable): array
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

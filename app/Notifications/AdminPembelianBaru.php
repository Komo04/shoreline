<?php

namespace App\Notifications;

use App\Models\Transaksi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AdminPembelianBaru extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Transaksi $trx)
    {
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'admin_pembelian_baru',
            'transaksi_id' => $this->trx->id,
            'kode_transaksi' => $this->trx->kode_transaksi,
            'user_id' => $this->trx->user_id,
            'status' => $this->trx->status_transaksi,
            'message' => "Pembelian baru: {$this->trx->kode_transaksi}",
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Pembelian Baru: {$this->trx->kode_transaksi}")
            ->greeting("Halo Admin!")
            ->line("Ada pembelian baru masuk.")
            ->line("Kode Transaksi: {$this->trx->kode_transaksi}")
            ->line("Status: {$this->trx->status_transaksi}")
            ->action('Lihat Detail Transaksi', route('admin.transaksi.show', $this->trx->id));
    }
}

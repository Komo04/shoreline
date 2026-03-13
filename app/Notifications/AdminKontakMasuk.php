<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminKontakMasuk extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $data)
    {
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $nama = (string) ($this->data['nama'] ?? '-');
        $email = (string) ($this->data['email'] ?? '-');
        $subjek = (string) ($this->data['subjek'] ?? '-');
        $pesan = (string) ($this->data['pesan'] ?? '-');

        return [
            'type' => 'admin_kontak_masuk',
            'kontak_id' => $this->data['kontak_id'] ?? null,
            'nama' => $nama,
            'email' => $email,
            'subjek' => $subjek,
            'pesan' => $pesan,
            'message' => "Kontak masuk dari {$nama} ({$email})",
            'url' => isset($this->data['kontak_id'])
                ? route('admin.kontak.show', ['kontak' => $this->data['kontak_id']])
                : route('admin.kontak.index'),
        ];
    }
}

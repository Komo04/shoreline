<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminUlasanBaru extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $produkId,
        public string $namaProduk,
        public int $userId,
        public string $userName,
        public int $rating
    ) {
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'admin_ulasan_baru',
            'produk_id' => $this->produkId,
            'user_id' => $this->userId,
            'rating' => $this->rating,
            'message' => "Ulasan baru {$this->rating}* untuk {$this->namaProduk} dari {$this->userName}",
        ];
    }
}

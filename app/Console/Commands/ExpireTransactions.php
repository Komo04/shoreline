<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaksi;

class ExpireTransactions extends Command
{
    protected $signature = 'trx:expire';
    protected $description = 'Expire transaksi yang melewati payment_deadline';

    public function handle(): int
    {
        $now = now();
        $count = Transaksi::where('status_transaksi', 'pending')
            ->whereNotNull('payment_deadline')
            ->where('payment_deadline', '<', $now)
            ->update(['status_transaksi' => 'expired']);

        $this->info("Expired {$count} transaksi.");
        return self::SUCCESS;
    }
}

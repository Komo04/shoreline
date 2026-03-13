<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$trx = App\Models\Transaksi::with('user')->find(10);
if (! $trx || ! $trx->user) {
    echo 'transaksi_not_found' . PHP_EOL;
    exit(1);
}

$trx->user->notify(new App\Notifications\UserStatusPesananDatabaseNotification($trx, 'paid', 'dikirim_test'));
$trx->user->notify(new App\Notifications\UserStatusPesananDiupdate($trx, 'paid', 'dikirim_test'));

echo 'queued_jobs=' . Illuminate\Support\Facades\DB::table('jobs')->count() . PHP_EOL;
echo 'latest_notification=' . Illuminate\Support\Facades\DB::table('notifications')->where('notifiable_id', $trx->user->id)->latest('id')->value('type') . PHP_EOL;

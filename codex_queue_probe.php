<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\DB::table('jobs')->truncate();
Illuminate\Support\Facades\DB::table('failed_jobs')->truncate();

echo 'jobs=' . Illuminate\Support\Facades\DB::table('jobs')->count() . PHP_EOL;
echo 'failed=' . Illuminate\Support\Facades\DB::table('failed_jobs')->count() . PHP_EOL;

$trx = App\Models\Transaksi::with('user')->whereNotNull('user_id')->latest('id')->first();
if (! $trx || ! $trx->user) {
    echo 'none' . PHP_EOL;
    exit(0);
}

echo 'trx=' . $trx->id . PHP_EOL;
echo 'kode=' . $trx->kode_transaksi . PHP_EOL;
echo 'email=' . $trx->user->email . PHP_EOL;
echo 'status=' . $trx->status_transaksi . PHP_EOL;

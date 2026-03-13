<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$svc = $app->make(App\Services\Midtrans\MidtransService::class);
echo get_class($svc), PHP_EOL;

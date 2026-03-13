<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$route = app('router')->getRoutes()->getByName('admin.customer.destroy');
echo $route ? 'FOUND' : 'NOT_FOUND';
if ($route) {
    echo PHP_EOL . implode(',', $route->methods()) . PHP_EOL . $route->uri();
}

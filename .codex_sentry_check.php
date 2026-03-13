<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
echo 'sample=' . var_export(env('SENTRY_SAMPLE_RATE'), true) . PHP_EOL;
echo 'traces=' . var_export(env('SENTRY_TRACES_SAMPLE_RATE'), true) . PHP_EOL;
echo 'cfgsample=' . var_export(config('sentry.sample_rate'), true) . PHP_EOL;
echo 'cfgtraces=' . var_export(config('sentry.traces_sample_rate'), true) . PHP_EOL;

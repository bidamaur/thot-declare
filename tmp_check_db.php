<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo 'DB path: ' . config('database.connections.sqlite.database') . PHP_EOL;
echo 'Default connection: ' . config('database.default') . PHP_EOL;
$dbPath = config('database.connections.sqlite.database');
echo 'File exists: ' . (file_exists($dbPath) ? 'yes' : 'no') . PHP_EOL;
echo 'Absolute path: ' . realpath($dbPath) . PHP_EOL;

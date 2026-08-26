<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$targets = ['CDR_VILLE_REGION','CDR_NAEMA'];
try {
    $in = implode("','", $targets);
    $sql = "SELECT owner, table_name FROM all_tables WHERE table_name IN ('$in') ORDER BY owner";
    $rows = DB::select($sql);
    echo json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([ 'error' => $e->getMessage() ]);
}

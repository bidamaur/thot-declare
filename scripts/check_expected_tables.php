<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$expected = ['BKCLI','BKADCLI','BKTELCLI','BKCNTCLI','BKICLI','CDR_VILLE_REGION','CDR_NAEMA'];
try {
    $inList = implode("','", $expected);
    $sql = "SELECT table_name FROM user_tables WHERE table_name IN ('$inList')";
    $rows = DB::select($sql);
    $found = array_map(function($r){ return $r->TABLE_NAME ?? $r->table_name ?? null; }, $rows);
    echo json_encode(['found' => $found, 'expected' => $expected], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([ 'error' => $e->getMessage() ]);
}

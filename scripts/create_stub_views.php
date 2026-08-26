<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$stmts = [
    "CREATE OR REPLACE VIEW CDR_VILLE_REGION AS SELECT NULL AS NOM_VILLE, 0 AS CODE_REGION, 0 AS CODE_VILLE FROM DUAL WHERE 1=0",
    "CREATE OR REPLACE VIEW CDR_NAEMA AS SELECT NULL AS SECT, NULL AS VAL FROM DUAL WHERE 1=0",
];

try {
    foreach ($stmts as $s) {
        DB::statement($s);
        echo "OK: Created/updated view.\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::statement('DROP TABLE IF EXISTS payments');
DB::table('migrations')->where('migration', '2026_06_25_000000_create_payments_table')->delete();

echo "Done\n";

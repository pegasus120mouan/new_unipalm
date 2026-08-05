<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE vehicules MODIFY COLUMN type_vehicule ENUM('moto', 'voiture', 'tricycle') NOT NULL DEFAULT 'voiture'");
    }

    public function down(): void
    {
        DB::table('vehicules')
            ->where('type_vehicule', 'tricycle')
            ->update(['type_vehicule' => 'voiture']);

        DB::statement("ALTER TABLE vehicules MODIFY COLUMN type_vehicule ENUM('moto', 'voiture') NOT NULL DEFAULT 'voiture'");
    }
};

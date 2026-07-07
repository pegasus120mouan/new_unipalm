<?php

use App\Models\Commis;
use App\Services\CommisService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('commis')) {
            return;
        }

        if (! Schema::hasColumn('commis', 'code_pin')) {
            Schema::table('commis', function (Blueprint $table) {
                $table->char('code_pin', 6)->nullable()->after('contact');
            });
        }

        $service = app(CommisService::class);

        Commis::query()
            ->whereNull('code_pin')
            ->orderBy('id_commis')
            ->each(function (Commis $commis) use ($service) {
                $commis->update(['code_pin' => $service->generateUniquePin()]);
            });

        Schema::table('commis', function (Blueprint $table) {
            $table->char('code_pin', 6)->nullable(false)->change();
            $table->unique('code_pin');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('commis') || ! Schema::hasColumn('commis', 'code_pin')) {
            return;
        }

        Schema::table('commis', function (Blueprint $table) {
            $table->dropUnique(['code_pin']);
            $table->dropColumn('code_pin');
        });
    }
};

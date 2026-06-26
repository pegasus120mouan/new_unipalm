<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('role_permissions')) {
            return;
        }

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 50);
            $table->string('module', 100);
            $table->timestamps();

            $table->unique(['role', 'module']);
            $table->index('role');
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }

    private function seedDefaults(): void
    {
        $defaults = config('modules.defaults', []);
        $allModules = collect(config('modules.groups', []))
            ->flatMap(fn (array $group) => array_keys($group['modules']))
            ->values()
            ->all();

        $now = now();
        $rows = [];

        foreach ($defaults as $role => $modules) {
            $moduleList = $modules === '*' ? $allModules : $modules;

            foreach ($moduleList as $module) {
                $rows[] = [
                    'role' => $role,
                    'module' => $module,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('role_permissions')->insert($rows);
        }
    }
};

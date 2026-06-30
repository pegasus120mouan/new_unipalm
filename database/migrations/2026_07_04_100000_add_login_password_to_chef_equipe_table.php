<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chef_equipe', function (Blueprint $table) {
            if (! Schema::hasColumn('chef_equipe', 'login')) {
                $table->string('login', 100)->nullable()->unique()->after('token');
            }
            if (! Schema::hasColumn('chef_equipe', 'password')) {
                $table->string('password', 255)->nullable()->after('login');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chef_equipe', function (Blueprint $table) {
            if (Schema::hasColumn('chef_equipe', 'password')) {
                $table->dropColumn('password');
            }
            if (Schema::hasColumn('chef_equipe', 'login')) {
                $table->dropUnique(['login']);
                $table->dropColumn('login');
            }
        });
    }
};

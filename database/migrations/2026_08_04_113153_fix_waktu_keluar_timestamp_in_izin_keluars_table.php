<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('izin_keluars', function (Blueprint $table) {
                $table->timestamp('waktu_keluar')->useCurrent()->change();
            });

            return;
        }

        DB::statement("ALTER TABLE izin_keluars MODIFY waktu_keluar TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('izin_keluars', function (Blueprint $table) {
                $table->timestamp('waktu_keluar')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE izin_keluars MODIFY waktu_keluar TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }
};

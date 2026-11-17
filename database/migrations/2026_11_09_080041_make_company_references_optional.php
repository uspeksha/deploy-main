<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
        });

        Schema::table('overtime_rules', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (
            DB::table('departments')->whereNull('company_id')->exists()
            || DB::table('overtime_rules')->whereNull('company_id')->exists()
        ) {
            throw new RuntimeException(
                'Cannot restore required company references while departments or overtime rules contain global records.',
            );
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });

        Schema::table('overtime_rules', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }
};

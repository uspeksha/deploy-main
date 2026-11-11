<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ptkp_statuses', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->date('effective_at')->nullable()->after('code');
        });

        DB::table('ptkp_statuses')
            ->whereNull('effective_at')
            ->update(['effective_at' => '2016-01-01']);

        Schema::table('ptkp_statuses', function (Blueprint $table) {
            $table->date('effective_at')->nullable(false)->change();
            $table->unique(['code', 'effective_at']);
        });
    }

    public function down(): void
    {
        Schema::table('ptkp_statuses', function (Blueprint $table) {
            $table->dropUnique(['code', 'effective_at']);
            $table->unique('code');
            $table->dropColumn('effective_at');
        });
    }
};

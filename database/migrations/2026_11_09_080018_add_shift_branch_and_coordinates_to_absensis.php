<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_lng', 10, 7)->nullable();

            $table->index('shift_id');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            foreach (['shift_id', 'branch_id'] as $column) {
                $table->dropForeign([$column]);
                $table->dropIndex([$column]);
                $table->dropColumn($column);
            }

            $table->dropColumn([
                'check_in_lat',
                'check_in_lng',
                'check_out_lat',
                'check_out_lng',
            ]);
        });
    }
};

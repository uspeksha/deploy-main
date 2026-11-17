<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->foreignId('leave_type_id')->nullable()->constrained()->nullOnDelete();
            $table->index('leave_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropForeign(['leave_type_id']);
            $table->dropIndex(['leave_type_id']);
            $table->dropColumn('leave_type_id');
        });
    }
};

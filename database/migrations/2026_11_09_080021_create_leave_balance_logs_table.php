<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_balance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('absensi_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 7, 2);
            $table->string('reason');
            $table->timestamps();

            $table->index('leave_balance_id');
            $table->index('absensi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balance_logs');
    }
};

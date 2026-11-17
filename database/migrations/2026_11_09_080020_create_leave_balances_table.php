<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('quota', 5, 2)->default(0);
            $table->decimal('used', 5, 2)->default(0);
            $table->date('expired_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'leave_type_id', 'year']);
            $table->index('user_id');
            $table->index('leave_type_id');
            $table->index('year');
            $table->index('expired_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};

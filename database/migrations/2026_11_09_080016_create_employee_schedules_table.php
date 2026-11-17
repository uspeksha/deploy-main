<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->timestamps();

            $table->unique(['user_id', 'work_date']);
            $table->index('user_id');
            $table->index('shift_id');
            $table->index('work_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedules');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bpjs_rates', function (Blueprint $table) {
            $table->id();
            $table->string('program', 20);
            $table->decimal('employee_rate', 6, 4);
            $table->decimal('employer_rate', 6, 4)->nullable();
            $table->unsignedBigInteger('salary_cap')->nullable();
            $table->date('effective_at');
            $table->timestamps();

            $table->unique(['program', 'effective_at']);
            $table->index('program');
            $table->index('effective_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bpjs_rates');
    }
};

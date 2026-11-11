<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_item_bpjs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bpjs_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('employee_amount');
            $table->bigInteger('employer_amount');
            $table->timestamps();

            $table->index('payroll_item_id');
            $table->index('bpjs_rate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_item_bpjs');
    }
};

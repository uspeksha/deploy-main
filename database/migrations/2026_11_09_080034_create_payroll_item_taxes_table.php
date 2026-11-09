<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_item_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('ptkp_status_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('taxable_income');
            $table->unsignedBigInteger('pph21_amount');
            $table->string('method', 30);
            $table->timestamps();

            $table->index('ptkp_status_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_item_taxes');
    }
};

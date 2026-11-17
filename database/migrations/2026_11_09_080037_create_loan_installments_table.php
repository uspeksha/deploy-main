<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_item_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('installment_no');
            $table->unsignedBigInteger('amount');
            $table->date('due_date');
            $table->string('status', 20);
            $table->timestamps();

            $table->unique(['loan_id', 'installment_no']);
            $table->index('payroll_item_id');
            $table->index('due_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_installments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('type_id')->constrained('reimbursement_types')->restrictOnDelete();
            $table->foreignId('payroll_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('receipt_path');
            $table->string('status', 20);
            $table->timestamps();

            $table->index('user_id');
            $table->index('type_id');
            $table->index('payroll_item_id');
            $table->index('approved_by');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reimbursements');
    }
};

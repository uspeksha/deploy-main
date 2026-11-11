<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('paid_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference', 100)->nullable();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->text('purpose')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
        });

        Schema::table('loan_installments', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['purpose', 'rejection_reason', 'approved_at']);
        });

        Schema::table('loan_installments', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'payment_reference']);
        });

        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paid_by');
            $table->dropColumn(['description', 'rejection_reason', 'paid_at', 'payment_reference']);
        });
    }
};

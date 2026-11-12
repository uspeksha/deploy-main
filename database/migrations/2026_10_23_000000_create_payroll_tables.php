<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('period_number');
            $table->unsignedSmallInteger('revision')->default(1);
            $table->foreignId('revision_of_id')->nullable()->constrained('payroll_periods')->nullOnDelete();
            $table->text('correction_reason')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('draft');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month', 'period_number', 'revision']);
            $table->index(['status', 'start_date']);
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('employee_id', 100)->nullable();
            $table->string('employee_name');
            $table->string('employment_type', 50);
            $table->string('department')->nullable();
            $table->unsignedInteger('work_days')->default(0);
            $table->unsignedBigInteger('daily_rate');
            $table->unsignedBigInteger('gross_pay');
            $table->unsignedInteger('late_occurrences')->default(0);
            $table->unsignedInteger('total_late_minutes')->default(0);
            $table->unsignedBigInteger('late_deduction')->default(0);
            $table->unsignedBigInteger('other_deductions')->default(0);
            $table->bigInteger('net_pay');
            $table->json('attendance_snapshot');
            $table->string('slip_number', 100)->unique();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'user_id']);
            $table->index(['employee_id', 'employee_name']);
        });

        Schema::create('payroll_lateness_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('attendance_id');
            $table->date('work_date');
            $table->unsignedInteger('late_minutes');
            $table->unsignedInteger('penalty_blocks');
            $table->unsignedBigInteger('deduction_amount');
            $table->string('lateness_source', 100);
            $table->timestamps();

            $table->unique(['payroll_item_id', 'work_date']);
            $table->index('attendance_id');
        });

        Schema::create('payroll_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('adjustment_amount');
            $table->text('reason');
            $table->string('status', 20)->default('open');
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();
            $table->string('settlement_reference', 100)->nullable();
            $table->text('settlement_note')->nullable();
            $table->timestamps();

            $table->index(['payroll_period_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_corrections');
        Schema::dropIfExists('payroll_lateness_details');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_periods');
    }
};

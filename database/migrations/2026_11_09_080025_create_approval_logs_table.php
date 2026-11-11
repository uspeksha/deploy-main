<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_flow_step_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('action', 30);
            $table->text('note')->nullable();
            $table->dateTime('acted_at');
            $table->timestamps();

            // Deliberately polymorphic without a database FK, like notifications.target_id.
            $table->index(['subject_type', 'subject_id']);
            $table->index('approval_flow_step_id');
            $table->index('approver_id');
            $table->index('action');
            $table->index('acted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_flow_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('level');
            $table->string('approver_type', 30);
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['approval_flow_id', 'level']);
            $table->index('approval_flow_id');
            $table->index('role_id');
            $table->index('user_id');
            $table->index('approver_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_flow_steps');
    }
};

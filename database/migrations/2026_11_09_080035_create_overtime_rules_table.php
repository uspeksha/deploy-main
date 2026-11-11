<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('day_type', 20);
            $table->decimal('hour_from', 5, 2);
            $table->decimal('hour_to', 5, 2)->nullable();
            $table->decimal('multiplier', 4, 2);
            $table->date('effective_at');
            $table->timestamps();

            $table->index('company_id');
            $table->index('day_type');
            $table->index('effective_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_rules');
    }
};

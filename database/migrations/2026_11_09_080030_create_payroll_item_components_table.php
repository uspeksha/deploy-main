<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_item_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->nullable()->constrained('payroll_components')->nullOnDelete();
            $table->string('name_snapshot');
            $table->string('type', 20);
            $table->bigInteger('amount');
            $table->timestamps();

            $table->index('payroll_item_id');
            $table->index('component_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_item_components');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('min_income');
            $table->unsignedBigInteger('max_income')->nullable();
            $table->decimal('rate', 6, 4);
            $table->date('effective_at');
            $table->timestamps();

            $table->unique(['min_income', 'effective_at']);
            $table->index('effective_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_brackets');
    }
};

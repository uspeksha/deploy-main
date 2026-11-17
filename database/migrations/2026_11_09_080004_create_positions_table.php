<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_level_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index('department_id');
            $table->index('job_level_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};

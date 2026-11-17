<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject_type');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index('subject_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_flows');
    }
};

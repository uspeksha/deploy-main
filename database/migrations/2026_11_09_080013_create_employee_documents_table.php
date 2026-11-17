<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('doc_type');
            $table->string('file_path');
            $table->date('expired_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('expired_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};

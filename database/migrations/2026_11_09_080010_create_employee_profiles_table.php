<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('ptkp_status_id')->nullable()->constrained()->nullOnDelete();
            $table->text('nik_ktp')->nullable();
            $table->text('npwp')->nullable();
            $table->text('bpjs_kesehatan_no')->nullable();
            $table->text('bpjs_ketenagakerjaan_no')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 10)->nullable();
            $table->text('address')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('bank_account_no')->nullable();
            $table->timestamps();

            $table->index('ptkp_status_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};

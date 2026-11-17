<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_status', 20)->default('approved')->index();
            $table->text('nik')->nullable();
            $table->text('bank_name')->nullable();
            $table->text('bank_account_number')->nullable();
            $table->text('bank_account_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['registration_status']);
            $table->dropColumn([
                'registration_status',
                'nik',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
            ]);
        });
    }
};

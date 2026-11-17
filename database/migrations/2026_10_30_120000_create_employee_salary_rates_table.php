<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('daily_rate');
            $table->dateTime('effective_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'effective_at']);
            $table->index(['user_id', 'effective_at']);
        });

        DB::table('users')
            ->where('employment_type', 'freelance')
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                $rates = $users->map(fn (object $user): array => [
                    'user_id' => $user->id,
                    'daily_rate' => 120000,
                    'effective_at' => $user->created_at ?? '2000-01-01 00:00:00',
                    'recorded_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                DB::table('employee_salary_rates')->insert($rates);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_rates');
    }
};

<?php

namespace Database\Factories;

use App\Models\Absensi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Absensi>
 */
class AbsensiFactory extends Factory
{
    protected $model = Absensi::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'hadir',
            'status_approval' => 'approved',
            'check_in_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}

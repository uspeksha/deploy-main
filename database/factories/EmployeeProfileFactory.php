<?php

namespace Database\Factories;

use App\Models\EmployeeProfile;
use App\Models\PtkpStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeProfile>
 */
class EmployeeProfileFactory extends Factory
{
    protected $model = EmployeeProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ptkp_status_id' => null,
            'nik_ktp' => fake()->numerify('################'),
            'npwp' => fake()->numerify('###############'),
            'bpjs_kesehatan_no' => fake()->numerify('############'),
            'bpjs_ketenagakerjaan_no' => fake()->numerify('############'),
            'birth_date' => fake()->date(),
            'gender' => fake()->randomElement(['male', 'female']),
            'address' => fake()->address(),
            'bank_name' => fake()->company(),
            'bank_account_no' => fake()->numerify('################'),
        ];
    }

    public function withPtkpStatus(): static
    {
        return $this->state(fn (): array => [
            'ptkp_status_id' => PtkpStatus::factory(),
        ]);
    }
}

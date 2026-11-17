<?php

namespace Database\Factories;

use App\Models\Absensi;
use App\Models\AttendanceCorrection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceCorrection>
 */
class AttendanceCorrectionFactory extends Factory
{
    protected $model = AttendanceCorrection::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'absensi_id' => fn (array $attributes) => Absensi::factory()
                ->state(['user_id' => $attributes['user_id']]),
            'approved_by' => null,
            'requested_check_in' => null,
            'requested_check_out' => null,
            'reason' => fake()->sentence(),
            'status' => 'pending',
        ];
    }
}

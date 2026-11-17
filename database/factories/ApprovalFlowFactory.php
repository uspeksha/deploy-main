<?php

namespace Database\Factories;

use App\Models\ApprovalFlow;
use App\Models\ApprovalSubjectType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalFlow>
 */
class ApprovalFlowFactory extends Factory
{
    protected $model = ApprovalFlow::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(3),
            'subject_type' => ApprovalSubjectType::Absensi->value,
            'is_active' => false,
        ];
    }
}

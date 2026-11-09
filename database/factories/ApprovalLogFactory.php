<?php

namespace Database\Factories;

use App\Models\ApprovalFlowStep;
use App\Models\ApprovalLog;
use App\Models\ApprovalSubjectType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalLog>
 */
class ApprovalLogFactory extends Factory
{
    protected $model = ApprovalLog::class;

    public function definition(): array
    {
        return [
            'approval_flow_step_id' => ApprovalFlowStep::factory(),
            'approver_id' => User::factory(),
            'subject_type' => ApprovalSubjectType::Absensi->value,
            'subject_id' => fake()->numberBetween(1, 100000),
            'action' => ApprovalLog::ACTION_APPROVED,
            'note' => null,
            'acted_at' => now(),
        ];
    }
}

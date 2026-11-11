<?php

namespace Database\Factories;

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalFlowStep>
 */
class ApprovalFlowStepFactory extends Factory
{
    protected $model = ApprovalFlowStep::class;

    public function definition(): array
    {
        return [
            'approval_flow_id' => ApprovalFlow::factory(),
            'level' => 1,
            'approver_type' => ApprovalFlowStep::APPROVER_ROLE,
            'role_id' => fn () => Role::query()->firstOrCreate(['name' => 'hrd'])->id,
        ];
    }
}

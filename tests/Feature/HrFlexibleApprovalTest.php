<?php

use App\Models\Absensi;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalLog;
use App\Models\ApprovalSubjectType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('relates approval flows, steps, roles, users, and logs bidirectionally', function () {
    $role = Role::factory()->create(['name' => 'hrd']);
    $approver = User::factory()->create();
    $flow = ApprovalFlow::factory()->create([
        'subject_type' => ApprovalSubjectType::AttendanceCorrection->value,
    ]);
    $roleStep = ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'level' => 1,
        'role_id' => $role->id,
    ]);
    $log = ApprovalLog::factory()->create([
        'approval_flow_step_id' => $roleStep->id,
        'approver_id' => $approver->id,
        'action' => ApprovalLog::ACTION_REJECTED,
        'subject_type' => ApprovalSubjectType::AttendanceCorrection->value,
    ]);

    expect($flow->steps->contains($roleStep))->toBeTrue()
        ->and($roleStep->flow->is($flow))->toBeTrue()
        ->and($roleStep->role->is($role))->toBeTrue()
        ->and($role->approvalFlowSteps->contains($roleStep))->toBeTrue()
        ->and($roleStep->logs->first()->is($log))->toBeTrue()
        ->and($log->step->is($roleStep))->toBeTrue()
        ->and($log->approver->is($approver))->toBeTrue()
        ->and($approver->approvalLogs->contains($log))->toBeTrue();
});

it('enforces approver type and reference consistency in the model', function () {
    $flow = ApprovalFlow::factory()->create();
    $hrdRole = Role::factory()->create(['name' => 'hrd']);
    $otherRole = Role::factory()->create();

    expect(fn () => ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'approver_type' => ApprovalFlowStep::APPROVER_ROLE,
        'role_id' => null,
    ]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => ApprovalFlowStep::factory()->create([
            'approval_flow_id' => $flow->id,
            'approver_type' => 'direct_manager',
            'role_id' => null,
        ]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => ApprovalFlowStep::factory()->create([
            'approval_flow_id' => $flow->id,
            'approver_type' => ApprovalFlowStep::APPROVER_ROLE,
            'role_id' => $otherRole->id,
        ]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => ApprovalFlowStep::factory()->create([
            'approval_flow_id' => $flow->id,
            'level' => 2,
            'role_id' => $hrdRole->id,
        ]))->toThrow(InvalidArgumentException::class);

    expect(ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $flow->id,
        'role_id' => $hrdRole->id,
    ]))->toBeInstanceOf(ApprovalFlowStep::class);
});

it('enforces one step per level in a flow', function () {
    $step = ApprovalFlowStep::factory()->create(['level' => 1]);

    expect(fn () => ApprovalFlowStep::factory()->create([
        'approval_flow_id' => $step->approval_flow_id,
        'level' => 1,
    ]))->toThrow(QueryException::class);
});

it('keeps approval logs append-only through the model', function () {
    $log = ApprovalLog::factory()->create();
    $log->note = 'Changed note';

    expect(fn () => $log->save())->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class);
});

it('seeds only one inactive legacy mirror flow idempotently', function () {
    $this->seed(ReferenceSeeder::class);
    $this->seed(ReferenceSeeder::class);

    $flows = ApprovalFlow::query()->get();
    $flow = $flows->first();

    expect($flows)->toHaveCount(1)
        ->and($flow->name)->toBe('Absensi (mirror legacy)')
        ->and($flow->subject_type)->toBe(ApprovalSubjectType::Absensi->value)
        ->and($flow->is_active)->toBeFalse()
        ->and($flow->steps)->toHaveCount(1)
        ->and($flow->steps->first()->level)->toBe(1)
        ->and($flow->steps->first()->approver_type)->toBe(ApprovalFlowStep::APPROVER_ROLE)
        ->and($flow->steps->first()->role->name)->toBe('hrd');
});

it('rejects unknown subject aliases and approval actions', function () {
    expect(fn () => ApprovalFlow::factory()->create(['subject_type' => 'App\\Models\\Absensi']))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => ApprovalLog::factory()->create(['action' => 'reject']))
        ->toThrow(InvalidArgumentException::class);
});

it('keeps approval log subjects polymorphic and preserves nullable approver references', function () {
    $foreignKeys = collect(Schema::getForeignKeys('approval_logs'))
        ->pluck('columns')
        ->flatten()
        ->all();
    $userForeignKeys = collect(Schema::getForeignKeys('users'))
        ->pluck('columns')
        ->flatten()
        ->all();
    $stepForeignKeys = collect(Schema::getForeignKeys('approval_flow_steps'))
        ->pluck('columns')
        ->flatten()
        ->all();

    expect($foreignKeys)->toContain('approval_flow_step_id', 'approver_id')
        ->and($foreignKeys)->not->toContain('subject_type', 'subject_id')
        ->and(method_exists(Absensi::class, 'approvalLogs'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'manager_id'))->toBeTrue()
        ->and($userForeignKeys)->toContain('manager_id')
        ->and(Schema::hasColumn('approval_flow_steps', 'user_id'))->toBeTrue()
        ->and($stepForeignKeys)->toContain('user_id');
});

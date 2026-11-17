<?php

use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Database\Seeders\BackfillUserRoleIdsSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('relates departments and positions to employee records', function () {
    $department = Department::factory()->create();
    $jobLevel = JobLevel::factory()->create();
    $position = Position::factory()->for($department)->for($jobLevel)->create();
    $employee = User::factory()->create([
        'department_id' => $department->id,
        'position_id' => $position->id,
    ]);

    expect($position->department->is($department))->toBeTrue()
        ->and($position->jobLevel->is($jobLevel))->toBeTrue()
        ->and($employee->department->is($department))->toBeTrue()
        ->and($employee->position->is($position))->toBeTrue();
});

it('relates role permissions bidirectionally without using the legacy role string', function () {
    $role = Role::factory()->create(['name' => 'new-role']);
    $permission = Permission::factory()->create();
    $user = User::factory()->create([
        'role' => 'hrd',
        'role_id' => $role->id,
    ]);

    RolePermission::factory()->create([
        'role_id' => $role->id,
        'permission_id' => $permission->id,
    ]);

    expect($user->role)->toBe('hrd')
        ->and($user->rbacRole->is($role))->toBeTrue()
        ->and($role->users->contains($user))->toBeTrue()
        ->and($role->permissions->contains($permission))->toBeTrue()
        ->and($permission->roles->contains($role))->toBeTrue();
});

it('seeds reference roles and permissions idempotently', function () {
    $this->seed(ReferenceSeeder::class);
    $this->seed(ReferenceSeeder::class);

    expect(Role::query()->whereIn('name', ['hrd', 'admin', 'user', 'pkl'])->count())->toBe(4)
        ->and(Permission::query()->where('code', 'absensi.view')->count())->toBe(1)
        ->and(Permission::query()->where('module', 'payroll')->exists())->toBeTrue();
});

it('backfills only recognized role ids and clears unmatched role ids', function () {
    $this->seed(ReferenceSeeder::class);
    $hrd = Role::query()->where('name', 'hrd')->firstOrFail();
    $recognizedUser = User::factory()->create(['role' => 'hrd']);
    $unmatchedUser = User::factory()->create([
        'role' => 'legacy-role',
        'role_id' => $hrd->id,
    ]);

    $this->seed(BackfillUserRoleIdsSeeder::class);

    expect($recognizedUser->fresh()->role_id)->toBe($hrd->id)
        ->and($unmatchedUser->fresh()->role_id)->toBeNull()
        ->and($unmatchedUser->fresh()->role)->toBe('legacy-role');
});

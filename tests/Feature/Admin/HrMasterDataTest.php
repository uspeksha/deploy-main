<?php

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeSchedule;
use App\Models\JobLevel;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function hrdForMasterData(): User
{
    return User::factory()->create(['role' => 'hrd']);
}

it('denies access to admin master data for users who are not HRD', function (string $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get(route('admin.master-data.index', ['resource' => 'departments']))
        ->assertForbidden();
})->with(['admin', 'user', 'pkl']);

it('provides company and branch master-data resources', function () {
    $hrd = hrdForMasterData();

    $this->actingAs($hrd)
        ->get(route('admin.master-data.index', ['resource' => 'departments']))
        ->assertOk()
        ->assertSee('Perusahaan')
        ->assertSee('Cabang');

    $this->get(route('admin.master-data.index', ['resource' => 'companies']))->assertOk();
    $this->get(route('admin.master-data.index', ['resource' => 'branches']))->assertOk();

    $this->post(route('admin.master-data.store', ['resource' => 'companies']), [
        'name' => 'PT Contoh',
        'npwp' => '01.234.567.8-901.000',
        'address' => 'Jakarta',
    ])->assertSessionHas('success');

    $company = Company::query()->where('name', 'PT Contoh')->firstOrFail();
    $this->post(route('admin.master-data.store', ['resource' => 'branches']), [
        'company_id' => $company->id,
        'name' => 'Kantor Pusat',
        'latitude' => '-6.2',
        'longitude' => '106.8',
        'radius_m' => 250,
    ])->assertSessionHas('success');

    expect(Branch::query()->where('name', 'Kantor Pusat')->firstOrFail()->radius_m)->toBe(250);
});

it('shows placeholder warnings for shifts and leave types', function () {
    $this->actingAs(hrdForMasterData())
        ->get(route('admin.master-data.index', ['resource' => 'shifts']))
        ->assertOk()
        ->assertSee('belum dipakai untuk menghitung keterlambatan');

    $this->get(route('admin.master-data.index', ['resource' => 'leave-types']))
        ->assertOk()
        ->assertSee('Kuota dan status berbayar adalah placeholder kebijakan perusahaan');
});

it('validates required fields and department parent selections', function () {
    $this->actingAs(hrdForMasterData())
        ->post(route('admin.master-data.store', ['resource' => 'departments']), [
            'name' => '',
            'parent_id' => 999999,
        ])
        ->assertSessionHasErrors(['parent_id', 'name']);
});

it('creates every supported kind of HR master data', function () {
    $parentDepartment = Department::factory()->create();
    $level = JobLevel::factory()->create();

    $this->actingAs(hrdForMasterData());
    $this->post(route('admin.master-data.store', ['resource' => 'departments']), [
        'parent_id' => $parentDepartment->id,
        'name' => 'Departemen Anak',
    ])->assertSessionHas('success');

    $this->post(route('admin.master-data.store', ['resource' => 'job-levels']), [
        'name' => 'Senior',
        'rank_order' => 4,
    ])->assertSessionHas('success');

    $this->post(route('admin.master-data.store', ['resource' => 'positions']), [
        'department_id' => $parentDepartment->id,
        'job_level_id' => $level->id,
        'name' => 'Supervisor',
    ])->assertSessionHas('success');

    $this->post(route('admin.master-data.store', ['resource' => 'leave-types']), [
        'name' => 'Cuti Pengganti',
        'default_quota' => 1,
        'is_paid' => '1',
        'need_attachment' => '1',
    ])->assertSessionHas('success');

    expect(Department::query()->where('name', 'Departemen Anak')->value('parent_id'))->toBe($parentDepartment->id)
        ->and(JobLevel::query()->where('name', 'Senior')->value('rank_order'))->toBe(4)
        ->and(Position::query()->where('name', 'Supervisor')->exists())->toBeTrue()
        ->and(LeaveType::query()->where('name', 'Cuti Pengganti')->value('is_paid'))->toBeTrue();
});

it('rejects deleting a job level that is used by a position', function () {
    $level = JobLevel::factory()->create();
    Position::factory()->create(['job_level_id' => $level->id]);

    $this->actingAs(hrdForMasterData())
        ->delete(route('admin.master-data.destroy', ['resource' => 'job-levels', 'record' => $level->id]))
        ->assertSessionHas('error');

    expect(JobLevel::query()->whereKey($level->id)->exists())->toBeTrue();
});

it('rejects deleting a leave type with an existing leave balance', function () {
    $type = LeaveType::factory()->create();
    LeaveBalance::factory()->create(['leave_type_id' => $type->id]);

    $this->actingAs(hrdForMasterData())
        ->delete(route('admin.master-data.destroy', ['resource' => 'leave-types', 'record' => $type->id]))
        ->assertSessionHas('error');

    expect(LeaveType::query()->whereKey($type->id)->exists())->toBeTrue();
});

it('prevents department self-parenting and descendant cycles', function () {
    $parent = Department::factory()->create();
    $child = Department::factory()->create([
        'parent_id' => $parent->id,
    ]);

    $this->actingAs(hrdForMasterData())
        ->put(route('admin.master-data.update', ['resource' => 'departments', 'record' => $parent->id]), [
            'parent_id' => $child->id,
            'name' => $parent->name,
        ])
        ->assertSessionHasErrors('parent_id');
    $this->put(route('admin.master-data.update', ['resource' => 'departments', 'record' => $parent->id]), [
        'parent_id' => $parent->id,
        'name' => $parent->name,
    ])->assertSessionHasErrors('parent_id');

    expect($parent->fresh()->parent_id)->toBeNull();
});

it('keeps department, position, shift, and leave-type records when referenced', function () {
    $department = Department::factory()->create();
    $childDepartment = Department::factory()->create([
        'parent_id' => $department->id,
    ]);
    $level = JobLevel::factory()->create();
    $position = Position::factory()->create([
        'department_id' => $department->id,
        'job_level_id' => $level->id,
    ]);
    $employee = User::factory()->create([
        'department_id' => $department->id,
        'position_id' => $position->id,
    ]);
    $shift = Shift::factory()->create();
    EmployeeSchedule::factory()->create([
        'user_id' => $employee->id,
        'shift_id' => $shift->id,
    ]);
    $leaveType = LeaveType::factory()->create();
    LeaveBalance::factory()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    $this->actingAs(hrdForMasterData());
    foreach ([
        ['departments', $department],
        ['job-levels', $level],
        ['positions', $position],
        ['shifts', $shift],
        ['leave-types', $leaveType],
    ] as [$resource, $record]) {
        $this->delete(route('admin.master-data.destroy', [
            'resource' => $resource,
            'record' => $record->id,
        ]))->assertSessionHas('error');

        expect($record->newQuery()->whereKey($record->id)->exists())->toBeTrue();
    }

    expect(Department::query()->whereKey($childDepartment->id)->exists())->toBeTrue();
});

it('records successful master-data deletion in activity logs', function () {
    $level = JobLevel::factory()->create();

    $this->actingAs(hrdForMasterData())
        ->delete(route('admin.master-data.destroy', ['resource' => 'job-levels', 'record' => $level->id]))
        ->assertSessionHas('success');

    expect(JobLevel::query()->whereKey($level->id)->exists())->toBeFalse()
        ->and(ActivityLog::query()->where('action', 'HR master data deleted')->exists())->toBeTrue();
});

it('renders list, create, and edit forms for every master-data resource', function () {
    $company = Company::query()->create(['name' => 'PT Contoh']);
    $branch = Branch::query()->create([
        'company_id' => $company->id,
        'name' => 'Kantor Pusat',
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius_m' => 250,
    ]);
    $department = Department::factory()->create();
    $level = JobLevel::factory()->create();
    $position = Position::factory()->create([
        'department_id' => $department->id,
        'job_level_id' => $level->id,
    ]);
    $records = [
        'companies' => Company::query()->create(['name' => 'PT Contoh']),
        'companies' => $company,
        'branches' => $branch,
        'departments' => $department,
        'job-levels' => $level,
        'positions' => $position,
        'shifts' => Shift::factory()->create(),
        'leave-types' => LeaveType::factory()->create(),
    ];

    $this->actingAs(hrdForMasterData());
    foreach ($records as $resource => $record) {
        $this->get(route('admin.master-data.index', ['resource' => $resource]))->assertOk();
        $this->get(route('admin.master-data.create', ['resource' => $resource]))->assertOk();
        $this->get(route('admin.master-data.edit', [
            'resource' => $resource,
            'record' => $record->id,
        ]))->assertOk();
    }
});

it('validates master-data checkboxes and stores an unchecked boolean as false', function () {
    $this->actingAs(hrdForMasterData())
        ->post(route('admin.master-data.store', ['resource' => 'shifts']), [
            'name' => 'Pagi',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'late_tolerance_min' => 0,
        ])
        ->assertRedirect(route('admin.master-data.index', ['resource' => 'shifts']));

    expect(Shift::query()->where('name', 'Pagi')->firstOrFail()->is_overnight)->toBeFalse();
});

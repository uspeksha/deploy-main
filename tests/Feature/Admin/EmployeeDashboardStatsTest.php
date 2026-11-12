<?php

use App\Models\Absensi;
use App\Models\User;

function createDashboardStatsAttendance(
    User $user,
    string $status,
    string $date,
    int $lateMinutes = 0,
    string $approval = 'approved',
    ?int $parentId = null,
): Absensi {
    return Absensi::create([
        'user_id' => $user->id,
        'status' => $status,
        'status_approval' => $approval,
        'check_in_at' => "{$date} 08:00:00",
        'late_minutes' => $lateMinutes,
        'parent_id' => $parentId,
        'is_mocked' => false,
    ]);
}

it('shows monthly gender totals and separate top three approved employee rankings', function () {
    $hrd = User::factory()->create([
        'role' => 'hrd',
        'registration_status' => 'approved',
    ]);

    $employees = collect([
        ['name' => 'Alpha Rank', 'gender' => 'male', 'attendance' => 5, 'sick' => 4, 'late' => 5, 'leave' => 3],
        ['name' => 'Bravo Rank', 'gender' => 'female', 'attendance' => 4, 'sick' => 3, 'late' => 4, 'leave' => 2],
        ['name' => 'Charlie Rank', 'gender' => null, 'attendance' => 4, 'sick' => 3, 'late' => 4, 'leave' => 2],
        ['name' => 'Delta Rank', 'gender' => 'male', 'attendance' => 3, 'sick' => 2, 'late' => 3, 'leave' => 1],
        ['name' => 'Echo Inactive', 'gender' => 'female', 'attendance' => 8, 'sick' => 8, 'late' => 8, 'leave' => 8, 'is_active' => false],
        ['name' => 'Foxtrot Pending', 'gender' => 'female', 'attendance' => 9, 'sick' => 9, 'late' => 9, 'leave' => 9, 'approval' => 'pending'],
    ])->map(function (array $stats) {
        $user = User::factory()->create([
            'name' => $stats['name'],
            'gender' => $stats['gender'],
            'role' => 'user',
            'registration_status' => 'approved',
            'is_active' => $stats['is_active'] ?? true,
        ]);

        foreach ([
            'hadir' => $stats['attendance'],
            'sakit' => $stats['sick'],
            'telat' => $stats['late'],
            'izin' => $stats['leave'],
        ] as $status => $count) {
            for ($index = 0; $index < $count; $index++) {
                createDashboardStatsAttendance(
                    $user,
                    $status === 'telat' ? 'hadir' : $status,
                    '2026-11-03',
                    $status === 'telat' ? 10 : 0,
                    $stats['approval'] ?? 'approved',
                );
            }
        }

        return $user;
    });

    createDashboardStatsAttendance($employees[0], 'hadir', '2026-10-31', 10);
    $parent = createDashboardStatsAttendance($employees[1], 'izin', '2026-10-31');
    createDashboardStatsAttendance($employees[1], 'izin', '2026-11-03', 0, 'approved', $parent->id);

    $this->actingAs($hrd)
        ->get(route('admin.absensi.index', ['month' => 11, 'year' => 2026]))
        ->assertOk()
        ->assertViewHas('employeeGenderStats', [
            'male' => 2,
            'female' => 2,
            'unspecified' => 1,
        ])
        ->assertViewHas('employeeRankings', function (array $rankings): bool {
            foreach (['attendance', 'sick', 'late', 'leave'] as $category) {
                if ($rankings[$category]->count() !== 3) {
                    return false;
                }

                if ($rankings[$category]->pluck('name')->all() !== ['Alpha Rank', 'Bravo Rank', 'Charlie Rank']) {
                    return false;
                }
            }

            return true;
        })
        ->assertSee('Statistik Karyawan')
        ->assertSee('Paling Rajin Hadir')
        ->assertSee('Paling Sering Sakit')
        ->assertSee('Paling Sering Terlambat')
        ->assertSee('Paling Banyak Izin')
        ->assertDontSee('Echo Inactive')
        ->assertDontSee('Foxtrot Pending');
});

it('always shows three numbered slots for each ranking when there is no attendance data', function () {
    $hrd = User::factory()->create([
        'role' => 'hrd',
        'registration_status' => 'approved',
    ]);

    $this->actingAs($hrd)
        ->get(route('admin.absensi.index', ['month' => 11, 'year' => 2026]))
        ->assertOk()
        ->assertSee('data-ranking-slot="attendance-1"', false)
        ->assertSee('data-ranking-slot="attendance-2"', false)
        ->assertSee('data-ranking-slot="attendance-3"', false)
        ->assertSee('data-ranking-slot="sick-1"', false)
        ->assertSee('data-ranking-slot="sick-2"', false)
        ->assertSee('data-ranking-slot="sick-3"', false)
        ->assertSee('data-ranking-slot="late-1"', false)
        ->assertSee('data-ranking-slot="late-2"', false)
        ->assertSee('data-ranking-slot="late-3"', false)
        ->assertSee('data-ranking-slot="leave-1"', false)
        ->assertSee('data-ranking-slot="leave-2"', false)
        ->assertSee('data-ranking-slot="leave-3"', false)
        ->assertSee('Belum ada data')
        ->assertViewHas('employeeRankings', function (array $rankings): bool {
            foreach (['attendance', 'sick', 'late', 'leave'] as $category) {
                if ($rankings[$category]->isNotEmpty()) {
                    return false;
                }
            }

            return true;
        });
});

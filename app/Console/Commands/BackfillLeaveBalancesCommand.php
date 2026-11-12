<?php

namespace App\Console\Commands;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class BackfillLeaveBalancesCommand extends Command
{
    protected $signature = 'hr:backfill-leave-balances
        {--year= : Legacy leave year to migrate (YYYY)}
        {--leave-type= : Exact seeded leave_types.name, e.g. cuti_tahunan}
        {--dry-run : Show candidates without writing data}';

    protected $description = 'Manually copy legacy leave balances into the new leave balance table';

    public function handle(): int
    {
        $options = [
            'year' => $this->option('year'),
            'leave_type' => $this->option('leave-type'),
        ];
        $validator = Validator::make($options, [
            'year' => ['required', 'integer', 'between:1900,9999'],
            'leave_type' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::INVALID;
        }

        $year = (int) $options['year'];
        $leaveType = LeaveType::query()->where('name', $options['leave_type'])->first();

        if (! $leaveType) {
            $this->error("Unknown leave type: {$options['leave_type']}");

            return self::INVALID;
        }

        $dryRun = (bool) $this->option('dry-run');
        $wouldCreate = 0;
        $created = 0;
        $skippedYear = 0;
        $skippedExisting = 0;

        if ($dryRun) {
            $this->info('DRY RUN: no leave balances will be written.');
        }

        User::query()->orderBy('id')->chunkById(100, function ($users) use (
            $year,
            $leaveType,
            $dryRun,
            &$wouldCreate,
            &$created,
            &$skippedYear,
            &$skippedExisting,
        ): void {
            foreach ($users as $user) {
                if ((int) $user->tahun_cuti !== $year) {
                    $skippedYear++;

                    continue;
                }

                $key = [
                    'user_id' => $user->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,
                ];

                if (LeaveBalance::query()->where($key)->exists()) {
                    $skippedExisting++;

                    continue;
                }

                $used = (int) $user->total_cuti_diambil;
                $quota = (int) $user->sisa_cuti + $used;
                $wouldCreate++;

                if ($dryRun) {
                    $this->line("Would create user_id={$user->id}, quota={$quota}, used={$used}");

                    continue;
                }

                $balance = LeaveBalance::query()->firstOrCreate($key, [
                    'quota' => $quota,
                    'used' => $used,
                ]);

                if ($balance->wasRecentlyCreated) {
                    $created++;
                } else {
                    $skippedExisting++;
                }
            }
        });

        if ($dryRun) {
            $this->info("Would create: {$wouldCreate}; skipped year mismatch: {$skippedYear}; skipped existing: {$skippedExisting}");
        } else {
            $this->info("Created: {$created}; skipped year mismatch: {$skippedYear}; skipped existing: {$skippedExisting}");
        }

        return self::SUCCESS;
    }
}

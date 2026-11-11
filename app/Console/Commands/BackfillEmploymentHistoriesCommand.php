<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class BackfillEmploymentHistoriesCommand extends Command
{
    protected $signature = 'hr:backfill-employment-histories {--start-date= : Explicit employment start date (YYYY-MM-DD)}';

    protected $description = 'Create initial employment histories for users without history records';

    public function handle(): int
    {
        $startDate = $this->option('start-date');
        $validator = Validator::make(
            ['start_date' => $startDate],
            ['start_date' => ['required', 'date_format:Y-m-d']],
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('start_date'));

            return self::INVALID;
        }

        $created = 0;
        User::query()
            ->whereNotNull('employment_type')
            ->whereDoesntHave('employmentHistories')
            ->orderBy('id')
            ->chunkById(100, function ($users) use (&$created, $startDate): void {
                foreach ($users as $user) {
                    $user->employmentHistories()->create([
                        'employment_type' => $user->employment_type,
                        'start_date' => $startDate,
                        'note' => 'Initial history created by explicit manual backfill.',
                    ]);
                    $created++;
                }
            });

        $this->info("Employment history rows created: {$created}");

        return self::SUCCESS;
    }
}

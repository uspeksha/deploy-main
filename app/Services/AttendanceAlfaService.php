<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceAlfaService
{
    /**
     * @return list<string>
     */
    public function workDatesBetween(Carbon $from, Carbon $through): array
    {
        $from = $from->copy()->startOfDay();
        $through = $through->copy()->startOfDay();

        if ($from->gt($through)) {
            return [];
        }

        $holidayDates = Holiday::query()
            ->whereBetween('holiday_date', [$from->toDateString(), $through->toDateString()])
            ->pluck('holiday_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();

        $workDates = [];

        while ($from->lte($through)) {
            $date = $from->toDateString();

            if ($from->isWeekday() && ! $holidayDates->has($date)) {
                $workDates[] = $date;
            }

            $from->addDay();
        }

        return $workDates;
    }

    /**
     * @param  iterable<int>  $userIds
     * @return Collection<int, list<string>>
     */
    public function confirmedAttendanceDatesByUser(iterable $userIds, Carbon $from, Carbon $through): Collection
    {
        $userIds = collect($userIds)->values();

        if ($userIds->isEmpty() || $from->gt($through)) {
            return collect();
        }

        return Absensi::query()
            ->whereIn('user_id', $userIds)
            ->whereNotNull('check_in_at')
            ->whereBetween('check_in_at', [$from->copy()->startOfDay(), $through->copy()->endOfDay()])
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where('status', 'hadir')
                        ->where('status_approval', 'approved')
                        ->whereNull('parent_id');
                })->orWhere(function ($query): void {
                    $query->whereIn('status', ['izin', 'sakit'])
                        ->where('status_approval', 'approved');
                });
            })
            ->get(['user_id', 'check_in_at'])
            ->groupBy('user_id')
            ->mapWithKeys(fn (Collection $rows, int|string $userId): array => [
                (int) $userId => array_values(array_unique(
                    $rows->pluck('check_in_at')
                        ->map(fn ($date) => Carbon::parse($date)->toDateString())
                        ->all(),
                )),
            ]);
    }

    /**
     * @param  list<string>  $workDates
     * @param  list<string>  $attendanceDates
     * @return list<string>
     */
    public function missingWorkDates(array $workDates, array $attendanceDates): array
    {
        return array_values(array_diff($workDates, $attendanceDates));
    }
}

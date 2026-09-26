<?php

namespace Database\Seeders;

use App\Models\Model;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Date and user helpers shared by the transaction seeders, so seeded data lands
 * in last month and today, the two periods reports are checked against.
 */
trait SeedsTransactions
{
    /**
     * Sorted dates: random days of last month, then today.
     *
     * @return Collection<int, CarbonImmutable>
     */
    private function transactionDates(int $lastMonthCount, int $todayCount): Collection
    {
        $start = CarbonImmutable::today()->subMonthNoOverflow()->startOfMonth();
        $days = $start->daysInMonth;

        $lastMonth = collect(range(1, $lastMonthCount))
            ->map(fn () => $start->addDays(random_int(0, $days - 1)))
            ->sort()
            ->values();

        return $lastMonth->concat(array_fill(0, $todayCount, CarbonImmutable::today()));
    }

    private function clampToToday(CarbonImmutable $date): CarbonImmutable
    {
        return $date->min(CarbonImmutable::today());
    }

    /**
     * Align created/updated timestamps with the transaction date, as reports sort by them.
     */
    private function stampTimestamps(Model $model, CarbonInterface $date): void
    {
        $model->timestamps = false;
        $model->forceFill(['created_at' => $date, 'updated_at' => $date])->saveQuietly();
    }

    /**
     * @return Collection<int, User>
     */
    private function getUsers(): Collection
    {
        return once(fn () => User::query()->get());
    }
}

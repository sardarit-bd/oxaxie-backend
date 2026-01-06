<?php

namespace App\Filament\Widgets;

use App\Models\AllCase;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $userCount = User::count();
        $caseCount = AllCase::count();
        $resolvedCases = AllCase::where('status', 'resolved')->count();

        return [
            Stat::make('Total Users', $userCount)
                ->description('Total users registered'),

            Stat::make('Total Cases', $caseCount)
                ->description('Total cases created')
                ->color('warning'),

            Stat::make('Case Resolved', $resolvedCases)
                ->description('Total cases resolved')
                ->color('success'),
        ];
    }
}
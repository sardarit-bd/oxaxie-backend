<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class IssueTypesChart extends ChartWidget
{
    protected static ?int $sort = 3;
    protected ?string $heading = 'Issue Types Graph';

    protected function getData(): array
    {
        // Define all possible issue types
        $issueTypes = [
            'Landlord/Tenant',
            'Employment',
            'Contracts',
            'Consumer Rights',
            'Family',
            'Other',
        ];

        // Get counts from the database
        $counts = DB::table('all_cases')
            ->select('issue_type', DB::raw('count(*) as total'))
            ->whereIn('issue_type', $issueTypes)
            ->groupBy('issue_type')
            ->pluck('total', 'issue_type')
            ->toArray();

        // Ensure all types are present even if 0
        $series = [];
        foreach ($issueTypes as $type) {
            $series[] = $counts[$type] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'data' => $series,
                    'backgroundColor' => [
                        '#6366F1',
                        '#10B981',
                        '#F59E0B',
                        '#EF4444',
                        '#8B5CF6',
                        '#6B7280',
                    ],
                ],
            ],
            'labels' => $issueTypes,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

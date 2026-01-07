<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IssueTypesChart extends ChartWidget
{
    protected static ?int $sort = 3;
    protected ?string $heading = 'Issue Types Graph';

    protected function getExtraAttributes(): array
    {
        return [
            'style' => 'background-color: #ffffffff; border-radius: 0.5rem !important;',
        ];
    }

    protected function getData(): array
    {
        $counts = DB::table('all_cases')
            ->select('issue_type', DB::raw('count(*) as total'))
            ->whereNotNull('issue_type')
            ->groupBy('issue_type')
            ->pluck('total', 'issue_type')
            ->toArray();

        Log::info('Raw counts from DB:', $counts);

        $issueTypeMapping = [
            'landlord/tenant' => 'Landlord/Tenant',
            'employment' => 'Employment',
            'contracts' => 'Contracts',
            'consumer rights' => 'Consumer Rights',
            'family' => 'Family',
            'other' => 'Other',
        ];

        $labels = [];
        $series = [];
        
        foreach ($issueTypeMapping as $dbValue => $displayLabel) {
            
            $count = $counts[$dbValue] ?? $counts[$displayLabel] ?? 0;
            if ($count > 0) {
                $labels[] = $displayLabel;
                $series[] = $count;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Cases by Type',
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
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }
}
<?php

namespace App\Filament\Widgets;

use App\Models\AllCase;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;

class CaseComparisonChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected ?string $pollingInterval = '60s';
    protected static ?string $aspectRatio = null;
    protected ?string $heading = 'Case Comparison (Created VS Resolved)';

    protected function getHeight(): string
    {
        return 'clamp(280px, 55vh, 520px)';
    }


    protected function getExtraAttributes(): array
    {
        return [
            'style' => 'height: clamp(280px, 55vh, 520px);',
        ];
    }




    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }



    protected function getData(): array
    {
        $createdData = Trend::model(AllCase::class)
            ->between(start: now()->subDays(30), end: now())
            ->perDay()
            ->count();
            
        $resolvedData = Trend::query(AllCase::where('status', 'resolved'))
            ->between(start: now()->subDays(30), end: now())
            ->perDay()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Created',
                    'data' => $createdData->map(fn ($value) => $value->aggregate),
                    'borderColor' => '#ff6a00',
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(255, 106, 0, 0.1)',
                ],
                [
                    'label' => 'Resolved',
                    'data' => $resolvedData->map(fn ($value) => $value->aggregate),
                    'borderColor' => '#11998e',
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(17, 153, 142, 0.1)',
                ],
            ],
            'labels' => $createdData->map(fn ($value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

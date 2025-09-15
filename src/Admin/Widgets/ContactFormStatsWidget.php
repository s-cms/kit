<?php

namespace SmartCms\Kit\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use SmartCms\Forms\Models\ContactForm;
use Illuminate\Support\Carbon;

class ContactFormStatsWidget extends ChartWidget
{

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = 'week';

    public function getHeading(): ?string
    {
        return __('kit::admin.contact_form_submissions');
    }

    protected function getFilters(): ?array
    {
        return [
            'week' => __('kit::admin.last_week'),
            'month' => __('kit::admin.last_month'),
            '3months' => __('kit::admin.last_3_months'),
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter;

        return match ($filter) {
            'month' => $this->getMonthData(),
            '3months' => $this->get3MonthsData(),
            default => $this->getWeekData(),
        };
    }

    protected function getWeekData(): array
    {
        $labels = [];
        $data = [];
        $now = now();

        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $labels[] = $date->format('D, M j');

            $count = ContactForm::whereDate('created_at', $date->format('Y-m-d'))->count();
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => __('kit::admin.submissions'),
                    'data' => $data,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getMonthData(): array
    {
        $labels = [];
        $data = [];
        $now = now();

        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $labels[] = $date->format('M j');

            $count = ContactForm::whereDate('created_at', $date->format('Y-m-d'))->count();
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => __('kit::admin.submissions'),
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function get3MonthsData(): array
    {
        $labels = [];
        $data = [];
        $now = now();

        for ($i = 11; $i >= 0; $i--) {
            $startOfWeek = $now->copy()->subWeeks($i)->startOfWeek();
            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $labels[] = $startOfWeek->format('M j') . ' - ' . $endOfWeek->format('M j');

            $count = ContactForm::whereBetween('created_at', [
                $startOfWeek->format('Y-m-d H:i:s'),
                $endOfWeek->format('Y-m-d H:i:s'),
            ])->count();

            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => __('kit::admin.submissions'),
                    'data' => $data,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}

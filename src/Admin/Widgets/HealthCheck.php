<?php

namespace SmartCms\Kit\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HealthCheck extends StatsOverviewWidget
{
    protected static ?int $sort = 100;

    protected function getStats(): array
    {
        return [
            ...$this->getDiskSpaceUsage(),
            ...$this->getCpuLoad(),
            ...$this->getMemoryUsage(),
        ];
    }

    protected function getDiskSpaceUsage(): array
    {
        try {
            if (function_exists('disk_total_space') && function_exists('disk_free_space')) {
                $total = disk_total_space('/');
                $free = disk_free_space('/');
                $used = $total - $free;
            } else {
                $total = 0;
                $free = 0;
                $used = 0;
            }
        } catch (\Exception) {
            $total = 0;
            $free = 0;
            $used = 0;
        }

        return [
            Stat::make(__('kit::admin.disk_usage'), round($used / 1_073_741_824, 2) . ' / ' . round($total / 1_073_741_824, 2) . ' GB')
                ->chart([$used, $used, $used])
                ->color($free > 1_000_000 ? 'success' : 'danger')
                ->description(__('kit::admin.used_space')),
        ];
    }

    protected function getCpuLoad(): array
    {
        try {
            $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        } catch (\Exception) {
            $load = [0, 0, 0];
        }
        $icon = $load[0] > 80 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';

        return [
            Stat::make(__('kit::admin.cpu_load'), round($load[0], 2) . ' %')
                ->descriptionIcon($icon)
                ->chart([$load[0], $load[1], $load[2]])
                ->color($load[0] > 80 ? 'danger' : 'success')
                ->description(__('kit::admin.15_minute_average')),
        ];
    }

    protected function getMemoryUsage(): array
    {
        try {
            if (function_exists('shell_exec')) {
                $mem = shell_exec('free -m');
                preg_match('/Mem:\s+(\d+)\s+(\d+)/', $mem, $matches);
            } else {
                $matches = [0, 0];
            }
        } catch (\Exception) {
            $matches = [0, 0];
        }

        $total = $matches[1] ?? 0;
        $used = $matches[2] ?? 0;
        $percent = $total > 0 ? round(($used / $total) * 100, 2) : 0;

        return [
            Stat::make(__('kit::admin.memory_usage'), "{$used} MB / {$total} MB")
                ->description(__('kit::admin.percent_used', ['percent' => $percent]))
                ->chart([$percent, $percent, $percent])
                ->color($percent > 80 ? 'danger' : 'success'),
        ];
    }
}

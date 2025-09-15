<?php

namespace SmartCms\Kit\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use SmartCms\Kit\Contracts\UpdateCheckerInterface;
use SmartCms\Kit\Contracts\UpdateServiceInterface;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;

class InfoWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = '2';

    public function getStats(): array
    {
        return [
            Stat::make(__('kit::admin.current_version'), $this->getCurrentVersion())
                ->description($this->getVersionDescription())
                ->descriptionIcon($this->getVersionIcon())
                ->color($this->getVersionColor()),
            Stat::make(__('kit::admin.published_pages'), $this->getPublishedCount())
                ->description(__('kit::admin.published_pages_desc'))
                ->descriptionIcon('heroicon-m-eye')
                ->color('success')
                ->chart($this->getPublishedChart()),

            Stat::make(__('kit::admin.draft_pages'), $this->getDraftCount())
                ->description(__('kit::admin.draft_pages_desc'))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray')
                ->chart($this->getDraftChart()),

            Stat::make(__('kit::admin.scheduled_pages'), $this->getScheduledCount())
                ->description(__('kit::admin.scheduled_pages_desc'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info')
                ->chart($this->getScheduledChart()),
        ];
    }

    protected function getPublishedCount(): int
    {
        return Page::where('status', PageStatus::Published)->count();
    }

    protected function getDraftCount(): int
    {
        return Page::where('status', PageStatus::Draft)->count();
    }

    protected function getScheduledCount(): int
    {
        return Page::where('status', PageStatus::Scheduled)->count();
    }

    protected function getPublishedChart(): array
    {
        return $this->getLastWeekChart(PageStatus::Published);
    }

    protected function getDraftChart(): array
    {
        return $this->getLastWeekChart(PageStatus::Draft);
    }

    protected function getScheduledChart(): array
    {
        return $this->getLastWeekChart(PageStatus::Scheduled);
    }

    protected function getLastWeekChart(PageStatus $status): array
    {
        $data = [];
        $now = now();

        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $count = Page::where('status', $status)
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->count();
            $data[] = $count;
        }

        return $data;
    }

    protected function getVersionDescription(): string
    {
        if ($this->hasUpdates()) {
            return __('kit::admin.update_available', ['version' => $this->getLatestVersion()]);
        }

        return __('kit::admin.up_to_date');
    }

    protected function getVersionIcon(): string
    {
        if ($this->hasUpdates()) {
            return 'heroicon-m-check-circle';
        }

        return 'heroicon-m-arrow-up-circle';
    }

    protected function getVersionColor(): string
    {
        if ($this->hasUpdates()) {
            return 'warning';
        }

        return 'success';
    }

    protected function hasUpdates(): bool
    {
        $updateChecker = app(UpdateCheckerInterface::class);
        $details = $updateChecker->getUpdateNotifications();
        return $details && ($details['has_updates'] ?? false);
    }

    protected function getCurrentVersion(): string
    {
        $updateService = app(UpdateServiceInterface::class);

        return $updateService->getCurrentVersion();
    }
}

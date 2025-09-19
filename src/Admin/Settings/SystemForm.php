<?php

namespace SmartCms\Kit\Admin\Settings;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Tabs\Tab;
use SmartCms\TemplateBuilder\Models\Layout;

class SystemForm
{
    public static function make(): Tab
    {
        return Tab::make(__('kit::admin.system'))->schema([
            Toggle::make('system.maintenance')
                ->label(__('kit::admin.maintenance'))
                ->helperText(__('kit::admin.maintenance_hint'))
                ->formatStateUsing(fn ($state) => $state ?? false)
                ->required(),
            // Toggle::make('system.debug')
            //     ->label(__('kit::admin.debug'))
            //     ->helperText(__('kit::admin.debug_hint'))
            //     ->formatStateUsing(function ($state) {
            //         return $state ?? true;
            //     })
            //     ->required(),
            Flex::make([
                Action::make('download_logs')
                    ->label(__('kit::admin.download_logs'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->action(fn () => response()->download(storage_path('logs/laravel.log'))),

                Action::make('clear_logs')
                    ->label(__('kit::admin.clear_logs'))
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        file_put_contents(storage_path('logs/laravel.log'), '');
                        Notification::make()
                            ->title(__('kit::admin.cleared_logs'))
                            ->success()
                            ->send();
                    }),
            ]),
            Flex::make([
                Select::make('header')->options(Layout::query()->where('path', 'like', '%header%')->pluck('name', 'id'))->searchable(),
                Select::make('footer')->options(Layout::query()->where('path', 'like', '%footer%')->pluck('name', 'id'))->searchable(),
            ]),
        ]);
    }
}

<?php

namespace SmartCms\Kit\Actions\Admin;

use Closure;
use Filament\Actions\Action;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\Blade;

class GetViewButton
{
    public static function run(): Closure
    {
        return fn(): string => Blade::render('{{$action}}', [
            'action' => Action::make('view')
                ->link()
                ->label(__('filament-actions::view.single.label'))
                ->icon('heroicon-o-eye')
                ->iconPosition(IconPosition::After)
                ->iconSize('sm')
                ->size('sm')
                ->color('gray')
                ->url(url('/'))
                ->openUrlInNewTab(),
        ]);
    }
}

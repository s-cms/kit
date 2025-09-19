<?php

namespace SmartCms\Kit\Admin\Settings;

use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Tabs\Tab;

class ThemeForm
{
    public static function make(): Tab
    {
        return Tab::make(__('kit::admin.theme'))
            ->schema(function (): array {
                $theme = setting('theme', []);
                $theme = array_merge(config('theme', []), $theme);
                $schema = [];
                foreach ($theme as $key => $value) {
                    $schema[] = ColorPicker::make('theme.' . $key)
                        ->label(ucfirst($key))
                        ->formatStateUsing(fn ($state) => $state ?? '#000000')
                        ->default($value);
                }

                return $schema;
            })->columns(3);
    }
}

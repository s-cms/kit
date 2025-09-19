<?php

namespace SmartCms\Kit\Admin\Forms;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use SmartCms\Support\Admin\Components\Forms\NameField;

class PageNameField
{
    public static function make(?string $name = 'name'): TextInput
    {
        return NameField::make($name)->live(onBlur: true)->afterStateUpdated(function (string $state, string $operation, Set $set, Get $get): void {
            if ($operation === 'edit') {
                return;
            }
            $slug = Str::slug($state);
            $currentslug = $get('slug') ?? $slug;
            if (str_contains($slug, $currentslug)) {
                $set('slug', $slug);
            }
        });
    }
}

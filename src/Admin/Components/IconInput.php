<?php

namespace SmartCms\Kit\Admin\Components;

use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Forms\Components\Select;

class IconInput extends Select
{
    public static function make(?string $name = null): static
    {
        $icons = collect(LucideIcon::cases())->mapWithKeys(function (LucideIcon $icon): array {
            $iconHtml = \Filament\Support\generate_icon_html($icon)->toHtml();

            return [$icon->value => "<div style='display: flex; gap: 10px; align-items: center;'> $iconHtml <span class='text-sm'>{$icon->name}</span></div>"];
        });

        return parent::make($name)->options($icons)->wrapOptionLabels(false)->allowHtml()->searchable()->hint(fn () => str()->of("You can use any icon from <a href='https://lucide.dev' target='_blank'>Lucide</a> set")->toHtmlString());
    }
}

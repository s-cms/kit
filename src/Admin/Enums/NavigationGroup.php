<?php

namespace SmartCms\Kit\Admin\Enums;

use BackedEnum;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum NavigationGroup implements HasIcon, HasLabel
{
    case Design;
    case Plugins;
    case System;

    public function getIcon(): string | BackedEnum | null
    {
        return null;
        return match ($this) {
            self::Design => LucideIcon::Component,
            self::Plugins => LucideIcon::PlugZap2,
            self::System => LucideIcon::Settings2,
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Design => __('kit::admin.design'),
            self::Plugins => __('kit::admin.plugins'),
            self::System => __('kit::admin.system'),
        };
    }
}

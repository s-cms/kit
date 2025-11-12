<?php

namespace SmartCms\Kit\Admin\Pages;

use SmartCms\Kit\Admin\Enums\NavigationGroup;
use SmartCms\PanelTranslate\TranslatesPage as PanelTranslateTranslatesPage;
use UnitEnum;

class TranslatesPage extends PanelTranslateTranslatesPage
{
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return NavigationGroup::System;
    }

    public function getBreadcrumbs(): array
    {
        return [
            Dashboard::getUrl() => Dashboard::getNavigationLabel(),
            self::getUrl() => self::getNavigationLabel(),
        ];
    }
}

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

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.translates');
    }

    public function getBreadcrumbs(): array
    {
        return [
            Dashboard::getUrl() => Dashboard::getNavigationLabel(),
            self::getUrl() => self::getNavigationLabel(),
        ];
    }
}

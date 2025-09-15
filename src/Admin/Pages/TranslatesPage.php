<?php

namespace SmartCms\Kit\Admin\Pages;

use SmartCms\Kit\Admin\Clusters\System\SystemCluster;
use SmartCms\PanelTranslate\TranslatesPage as PanelTranslateTranslatesPage;

class TranslatesPage extends PanelTranslateTranslatesPage
{
    protected static ?int $navigationSort = 1;

    public static function getCluster(): ?string
    {
        return SystemCluster::class;
    }
}

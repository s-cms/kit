<?php

namespace SmartCms\Kit\Admin\Clusters\System;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class SystemCluster extends Cluster
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.system');
    }
}

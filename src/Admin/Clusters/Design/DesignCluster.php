<?php

namespace SmartCms\Kit\Admin\Clusters\Design;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class DesignCluster extends Cluster
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.design');
    }
}

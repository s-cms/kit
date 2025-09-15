<?php

namespace SmartCms\Kit\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use PDO;

class VersionsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 101;

    protected function getStats(): array
    {
        return [
            ...$this->getPhpVersion(),
            ...$this->getNodeVersion(),
            ...$this->getDatabaseCheck(),
            ...$this->getEnvironment(),
        ];
    }

    protected function getPhpVersion(): array
    {
        return [
            Stat::make(__('kit::admin.php_version'), phpversion())
        ];
    }

    protected function getNodeVersion(): array
    {
        $node = trim(shell_exec('node -v'));

        return [
            Stat::make(__('kit::admin.node_version'), $node ?? 'Not installed')
        ];
    }

    protected function getEnvironment(): array
    {
        return [
            Stat::make(__('kit::admin.environment'), config('app.env'))
        ];
    }

    protected function getDatabaseCheck(): array
    {
        $database = DB::connection()->getPdo();
        $driver = DB::connection()->getDriverName();
        $serverVersion = $database->getAttribute(PDO::ATTR_SERVER_VERSION);
        $versionParts = explode('-', $serverVersion);
        $databaseName = isset($versionParts[1]) ? $versionParts[1] : ucfirst($driver);
        $databaseVersion = $versionParts[0];

        return [
            Stat::make(__('kit::admin.database_version'), $databaseName . ' ' . $databaseVersion)
        ];
    }
}

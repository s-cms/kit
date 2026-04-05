<?php

namespace SmartCms\Kit\Actions\Admin;

use Closure;
use Composer\InstalledVersions;

class GetVersionHtml
{
    public static function run(): Closure
    {
        return function (): string {
            $version = InstalledVersions::getPrettyVersion('smart-cms/kit');

            return <<<HTML
            <div class="text-xs text-center text-gray-500" style=" font-size: 0.75rem;
            text-align: center;
            color: #6b7280;">
                <p>Powered by <a href="https://s-cms.dev" target="_blank" class="hover:text-gray-700">SmartCms</a> v.{$version}</p>
            </div>
            HTML;
        };
    }
}

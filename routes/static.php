<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use Laravel\Mcp\Facades\Mcp;
use SmartCms\Kit\Actions\Block\SyncBlockSchemas;
use SmartCms\Kit\Commands\ActivatePages;
use SmartCms\Kit\Http\Handlers\RobotsHandler;
use SmartCms\Kit\Http\Handlers\SitemapHandler;
use SmartCms\Kit\Mcp\Servers\SmartCmsServer;

Route::get('robots.txt', RobotsHandler::class)->name('robots');
Route::get('sitemap.xml', SitemapHandler::class)->name('sitemap');
Route::get('sitemap/{lang?}.xml', SitemapHandler::class)->name('sitemap.lang');

Mcp::local('smart-cms', SmartCmsServer::class);

Schedule::command(ActivatePages::class)->everyMinute();

Route::post('api/blocks/sync-schemas', function () {
    $result = SyncBlockSchemas::run();

    return response()->json($result);
})->name('api.blocks.sync-schemas')->middleware(['api']);

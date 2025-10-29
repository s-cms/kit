<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use SmartCms\Kit\Actions\Block\SyncBlockSchemas;
use SmartCms\Kit\Commands\ActivatePages;
use SmartCms\Kit\Http\Handlers\PageHandler;
use SmartCms\Kit\Http\Handlers\RobotsHandler;
use SmartCms\Kit\Http\Handlers\SitemapHandler;

Route::get('robots.txt', RobotsHandler::class)->name('robots');
Route::get('sitemap.xml', SitemapHandler::class)->name('sitemap');

Route::get('sitemap/{lang?}.xml', SitemapHandler::class)->name('sitemap.lang');
Route::get('/{slug?}/{second_slug?}/{third_slug?}', PageHandler::class)
    ->where('slug', '^(?!admin|api|_debugbar|.well-known).*$')
    ->where('lang', '[a-zA-Z]{2}')
    ->middleware(['web', 'maintenance', 'uuid', 'lang'])
    ->name('cms.page')
    ->multilingual();

Schedule::command(ActivatePages::class)->everyMinute();

Route::post('api/blocks/sync-schemas', function () {
    $result = SyncBlockSchemas::run();

    return response()->json($result);
})->name('api.blocks.sync-schemas')->middleware(['api']);
Route::get('api/blocks/sync-schemas', function () {
    $result = SyncBlockSchemas::run();

    return response()->json($result);
})->name('api.blocks.sync-schemas')->middleware(['api']);

<?php

use Illuminate\Support\Facades\Route;
use SmartCms\Kit\Http\Handlers\PageHandler;

// Page handler can be overrided, because its include on boot
Route::get('/{slug?}/{second_slug?}/{third_slug?}', PageHandler::class)
    ->where('slug', '^(?!admin|api|_debugbar|.well-known).*$')
    ->where('lang', '[a-zA-Z]{2}')
    ->middleware(['web', 'maintenance', 'uuid', 'lang'])
    ->name('cms.page')
    ->multilingual();

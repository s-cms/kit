<?php

use Illuminate\Support\Facades\Route;
use SmartCms\Kit\Http\Handlers\PageHandler;

// Page handler can be overrided, because its include on boot
if (config('kit.register_routes')) {
    // Support unlimited nesting depth (validated at model level to max 5)
    Route::get('/{path}', PageHandler::class)
        ->where('path', '^(?!admin|api|_debugbar|.well-known).*')
        ->middleware(['web', 'maintenance', 'uuid', 'lang'])
        ->name('cms.page')
        ->multilingual();
}

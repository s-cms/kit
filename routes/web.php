<?php

use Illuminate\Support\Facades\Route;
use SmartCms\Kit\Http\Controllers\MediaController;
use SmartCms\Kit\Http\Controllers\PreviewController;
use SmartCms\Kit\Http\Handlers\PageHandler;

// Media API endpoint
Route::get('/api/media/{id}', [MediaController::class, 'show'])
    ->name('api.media.show')
    ->middleware(['web', 'throttle:60,1']);

// Preview route with rate limiting (Layer 4: Rate Limiting)
Route::get('/preview/{token}', PreviewController::class)
    ->name('preview.show')
    ->middleware(['web', 'throttle:10,1']);

// Page handler can be overrided, because its include on boot
if (config('kit.register_routes')) {
    // Support unlimited nesting depth (validated at model level to max 5)
    Route::get('/{path}', PageHandler::class)
        ->where('path', '^(?!admin|api|_debugbar|.well-known).*')
        ->middleware(['web', 'maintenance', 'uuid', 'lang'])
        ->name('cms.page')
        ->multilingual();
}

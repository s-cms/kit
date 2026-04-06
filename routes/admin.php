<?php

use Illuminate\Support\Facades\Route;
use SmartCms\Kit\Http\Controllers\MediaPickerController;

// Media Picker Routes - Protected by admin auth
Route::prefix('admin/media-picker')
    ->middleware(['web', 'auth:admin'])
    ->group(function () {
        Route::post('/upload', [MediaPickerController::class, 'upload'])->name('admin.media-picker.upload');
        Route::post('/fetch-url', [MediaPickerController::class, 'fetchUrl'])->name('admin.media-picker.fetch-url');
        Route::get('/unsplash/search', [MediaPickerController::class, 'unsplashSearch'])->name('admin.media-picker.unsplash.search');
        Route::post('/unsplash/download', [MediaPickerController::class, 'unsplashDownload'])->name('admin.media-picker.unsplash.download');
        Route::get('/library', [MediaPickerController::class, 'library'])->name('admin.media-picker.library');
        Route::post('/update', [MediaPickerController::class, 'update'])->name('admin.media-picker.update');
    });

<?php

namespace SmartCms\Kit\Observers;

use SmartCms\Kit\Jobs\ProcessMediaJob;
use SmartCms\Kit\Models\Media;
use SmartCms\Kit\Services\ImageProcessingService;

class MediaObserver
{
    /**
     * Handle the Media "created" event.
     */
    public function created(Media $media): void
    {
        // Only process images
        if (! $media->isImage()) {
            return;
        }

        // Skip SVG files
        if ($media->mime_type === 'image/svg+xml') {
            return;
        }

        // Process images in queue (or sync if queue is not configured)
        $connection = config('kit.media.queue_connection', 'sync');

        if ($connection === 'sync') {
            // Process immediately
            app(ImageProcessingService::class)->processImage($media);
        } else {
            // Process in queue
            ProcessMediaJob::dispatch($media)->onConnection($connection);
        }
    }

    /**
     * Handle the Media "deleting" event.
     */
    public function deleting(Media $media): void
    {
        // Files will be deleted by the model's deleteWithFiles method
        // This observer is here in case we need additional cleanup logic
    }
}

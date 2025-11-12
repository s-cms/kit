<?php

namespace SmartCms\Kit\Observers;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaObserver
{
    /**
     * Handle the Media "created" event.
     */
    public function created(Media $media): void
    {
        // Initialize alt text as empty array for all languages if not set
        if (! $media->hasCustomProperty('alt')) {
            $media->setCustomProperty('alt', []);
            $media->save();
        }

        // Extract and store image dimensions
        $this->extractImageDimensions($media);
    }

    /**
     * Handle the Media "updating" event.
     */
    public function updating(Media $media): void
    {
        // Ensure alt is always an array
        $alt = $media->getCustomProperty('alt', []);
        if (! is_array($alt)) {
            $media->setCustomProperty('alt', []);
        }
    }

    /**
     * Handle the Media "deleting" event.
     */
    public function deleting(Media $media): void
    {
        // Clean up any related files or conversions
        // Spatie handles most of this automatically, but we can add custom logic here
    }

    /**
     * Extract and store image dimensions in custom properties
     */
    protected function extractImageDimensions(Media $media): void
    {
        try {
            $path = $media->getPath();

            if (! file_exists($path)) {
                return;
            }

            $imageSize = @getimagesize($path);

            if ($imageSize !== false) {
                $media->setCustomProperty('width', $imageSize[0]);
                $media->setCustomProperty('height', $imageSize[1]);
                $media->saveQuietly(); // Save without triggering events again
            }
        } catch (\Exception $e) {
            // Silently fail if we can't extract dimensions
            logger()->warning('Failed to extract image dimensions for media ' . $media->id, [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

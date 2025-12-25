<?php

namespace SmartCms\Kit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use SmartCms\Kit\Models\Media;

class MediaController
{
    /**
     * Get media data by ID
     */
    public function show(int $id): JsonResponse
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(
                [
                    'error' => 'Media not found',
                ],
                404,
            );
        }

        if (!$media->isImage()) {
            return response()->json(
                [
                    'error' => 'Media is not an image',
                ],
                400,
            );
        }

        // Get alt text for current language
        $alt = $media->alt[current_lang()] ?? ($media->alt['alt'] ?? '');

        return response()->json([
            'alt' => $alt,
            'src' => $media->getUrl(),
            'srcset' => $media->getSrcset(),
            'width' => $media->width,
            'height' => $media->height,
        ]);
    }
}

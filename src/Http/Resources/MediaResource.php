<?php

namespace SmartCms\Kit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use SmartCms\Kit\Models\Media;

/**
 * HTTP Resource for Media - includes media file information and URLs for frontend use.
 */
class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        if (is_string($this->resource) || is_int($this->resource)) {
            $media = Media::find($this->resource);
        } else {
            /**
             * @var \SmartCms\Kit\Models\Media $media
             */
            $media = $this->resource;
        }
        $defaultSrc = no_image()['source'] ?? '';
        if (! $media || ! $media instanceof Media) {
            return [
                'alt' => '',
                'src' => $defaultSrc,
                'srcset' => '',
                'width' => 0,
                'height' => 0,
            ];
        }

        return [
            'alt' => $media->name,
            'src' => $media->getUrl() ?? $defaultSrc,
            'srcset' => $media->getSrcset(),
            'width' => $media->width,
            'height' => $media->height,
        ];
    }
}

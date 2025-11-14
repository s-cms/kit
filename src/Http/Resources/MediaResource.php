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
        if(is_string($this->resource) || is_int($this->resource)) {
            $media = Media::find($this->resource);
        } else {
            /**
             * @var \SmartCms\Kit\Models\Media $media
             */
            $media = $this->resource;
        }
        if(!$media ||! $media instanceof Media) {
            return [
                'alt' => '',
                'src' => no_image(),
                'srcset' => '',
                'width' => 0,
                'height' => 0,
            ];
        }
        return [
            'alt' => $media->name,
            'src' => $media->getUrl() ?? no_image(),
            'srcset' => $media->getSrcset(),
            'width' => $media->width,
            'height' => $media->height,
        ];
    }
}

<?php

namespace SmartCms\Kit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * HTTP Resource for SimplePage - includes only essential fields for frontend display.
 */
class SimplePageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'url' => $this->route(),
            'image' => $this->image,
            'banner' => $this->banner,
            'title' => $this->title,
            'heading' => $this->heading,
            'summary' => $this->summary,
            'content' => $this->content,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'depth' => $this->depth,
            'published_at' => $this->published_at?->toDateTimeString(),
            'views' => $this->views,
        ];
    }
}

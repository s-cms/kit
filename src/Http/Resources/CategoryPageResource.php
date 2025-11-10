<?php

namespace SmartCms\Kit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * HTTP Resource for CategoryPage - includes fields relevant for category display.
 */
class CategoryPageResource extends JsonResource
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
            'description' => $this->description,
            'depth' => $this->depth,
            'published_at' => $this->published_at?->toDateTimeString(),
            'views' => $this->views,
            'children_count' => $this->children()->count(),
        ];
    }
}

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
        /**
         * @var \SmartCms\Kit\Models\Page $page
         */
        $page = $this->resource;

        return [
            'id' => $page->id,
            'name' => $page->name,
            'slug' => $page->slug,
            'url' => $page->route(),
            'image' => new MediaResource($page->image),
            'banner' => new MediaResource($page->banner),
            'title' => $page->title,
            'heading' => $page->heading,
            'summary' => $page->summary,
            'description' => $page->description,
            'depth' => $page->depth,
            'published_at' => $page->published_at?->format('Y-m-d H:i:s'),
            'views' => $page->views,
            'children_count' => $page->children()->count(),
        ];
    }
}

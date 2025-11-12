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
        /**
         * @var \SmartCms\Kit\Models\Page $page
         */
        $page = $this->resource;
        return [
            'id' => $page->id,
            'name' => $page->name,
            'slug' => $page->slug,
            'url' => $page->route(),
            'image' => $page->image,
            'banner' => $page->banner,
            'title' => $page->title,
            'heading' => $page->heading,
            'summary' => $page->summary,
            'content' => $page->content,
            'description' => $page->description,
            'keywords' => $page->keywords,
            'depth' => $page->depth,
            'published_at' => $page->published_at?->format('Y-m-d H:i:s'),
            'views' => $page->views,
        ];
    }
}

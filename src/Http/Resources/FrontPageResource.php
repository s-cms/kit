<?php

namespace SmartCms\Kit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use SmartCms\Kit\Admin\Resources\Pages\PageResource;
use SmartCms\Kit\Support\Transformers\PageTransformer;

class FrontPageResource extends JsonResource
{
    /**
     * Additional options for transformation.
     */
    protected array $transformOptions = [];

    /**
     * Set transformation options.
     */
    public function withOptions(array $options): self
    {
        $this->transformOptions = $options;

        return $this;
    }

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Use PageTransformer pipeline for transformation
        return PageTransformer::transform($this->resource, $this->transformOptions);
    }

    /**
     * Create a resource with specific transformation options.
     */
    public static function withRelations($page, array $relations = []): self
    {
        if (! empty($relations)) {
            $page->load($relations);
        }
        // @phpstan-ignore-next-line
        return new static($page);
    }
}

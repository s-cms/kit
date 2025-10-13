<?php

namespace SmartCms\Kit\Support\Transformers;

use Illuminate\Pipeline\Pipeline;

class PageTransformer
{
    /**
     * Transform a page model to an array through registered pipes.
     */
    public static function transform($page, array $options = []): array
    {
        $pipes = static::getPipes();

        // Start with base page data
        $data = [
            'id' => $page->id,
            'name' => $page->name,
            'slug' => $page->slug,
            'url' => $page->route(),
            'breadcrumbs' => $page->getBreadcrumbs(),
            'image' => $page->image,
            'banner' => $page->banner,
            'title' => $page->title,
            'heading' => $page->heading,
            'summary' => $page->summary,
            'content' => $page->content,
            'description' => $page->description,
            'keywords' => $page->keywords,
            'settings' => $page->settings,
            'layout_settings' => $page->layout_settings,
            'is_root' => $page->is_root,
            'depth' => $page->depth,
        ];

        // Create transformation context
        $context = new TransformContext($page, $data, $options);

        // Pass through pipeline (custom pipes from config)
        $result = app(Pipeline::class)
            ->send($context)
            ->through($pipes)
            ->thenReturn();

        // Apply augmentation transformations
        if (method_exists($page, 'applyAugmentedTransformations')) {
            $page::applyAugmentedTransformations($result);
        }

        return $result->toArray();
    }

    /**
     * Get registered transformer pipes from config.
     */
    protected static function getPipes(): array
    {
        return config('kit.page_transformers', []);
    }
}

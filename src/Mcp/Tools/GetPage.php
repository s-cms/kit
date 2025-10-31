<?php

namespace SmartCms\Kit\Mcp\Tools;

use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use SmartCms\Kit\Models\Page;

#[IsReadOnly]
class GetPage extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'get_page';

    /**
     * The tool's description.
     */
    protected string $description = 'Get full page details including metadata, SEO, and attached blocks';

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Page ID'),
            'slug' => $schema->string()
                ->description('Page slug (alternative to id)'),
            'language' => $schema->string()
                ->description('Language code for translations'),
        ];
    }

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:pages,id',
            'slug' => 'nullable|string',
            'language' => 'nullable|string',
        ]);

        if (empty($validated['id']) && empty($validated['slug'])) {
            return Response::text('Either id or slug must be provided');
        }

        // Find the page
        $query = Page::with(['blocks']);
        if (! empty($validated['id'])) {
            $page = $query->find($validated['id']);
        } else {
            $page = $query->where('slug', $validated['slug'])->first();
        }

        if (! $page) {
            return Response::text('Page not found');
        }

        // Build response
        $result = [
            'id' => $page->id,
            'name' => $page->name,
            'slug' => $page->slug,
            'status' => $page->status,
            'layout' => $page->layout,
            'heading' => $page->heading,
            'content' => $page->content,
            'summary' => $page->summary,
            'seo' => [
                'meta_title' => $page->title,
                'meta_description' => $page->description,
                'meta_keywords' => $page->keywords,
            ],
            'blocks' => $page->blocks->map(fn($block) => [
                'id' => $block->id,
                'type' => $block->type,
                'title' => $block->title,
                'status' => $block->status,
                'position' => $block->pivot->position ?? null,
            ])->toArray(),
            'created_at' => $page->created_at?->toIso8601String(),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];

        return Response::json($result);
    }
}

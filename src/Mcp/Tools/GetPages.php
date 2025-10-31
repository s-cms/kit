<?php

namespace SmartCms\Kit\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Illuminate\JsonSchema\JsonSchema;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;

#[IsReadOnly]
class GetPages extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'get_pages';

    /**
     * The tool's description.
     */
    protected string $description = 'List all pages with basic metadata';

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum([PageStatus::Published->value, PageStatus::Draft->value, PageStatus::Scheduled->value])
                ->description('Filter by status'),
            'language' => $schema->string()
                ->description('Filter by language code'),
            'search' => $schema->string()
                ->description('Search term for title or slug'),
        ];
    }

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:' . PageStatus::Published->value . ',' . PageStatus::Draft->value . ',' . PageStatus::Scheduled->value,
            'language' => 'nullable|string',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Page::query();

        // Apply filters
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['search'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('name', 'like', "%{$validated['search']}%")
                    ->orWhere('slug', 'like', "%{$validated['search']}%");
            });
        }

        $pages = $query->orderBy('updated_at', 'desc')->get();

        $result = $pages->map(function ($page) {
            return [
                'id' => $page->id,
                'name' => $page->name,
                'slug' => $page->slug,
                'status' => $page->status,
                'blocks_count' => $page->blocks()->count(),
                'updated_at' => $page->updated_at?->toIso8601String(),
            ];
        });

        return Response::json($result);
    }
}

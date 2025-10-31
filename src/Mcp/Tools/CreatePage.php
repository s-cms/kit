<?php

namespace SmartCms\Kit\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Illuminate\JsonSchema\JsonSchema;
use SmartCms\Kit\Models\Page;

class CreatePage extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'create_page';

    /**
     * The tool's description.
     */
    protected string $description = 'Create a new page with validation';

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Page name')
                ->required(),
            'slug' => $schema->string()
                ->description('Page URL slug')
                ->required(),
            'status' => $schema->string()
                ->enum(['scheduled', 'draft', 'published'])
                ->description('Page status (true=published, false=draft)')
                ->default('draft'),
            'translations' => $schema->object()
                ->description('Multilingual data (e.g., {"en": {"name": "..."}, "uk": {...}})'),
        ];
    }

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug',
            'status' => 'string|in:scheduled,draft,published',
            'translations' => 'nullable|array',
        ]);

        try {
            $page = Page::query()->create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'status' => $validated['status'] ?? 'draft',
            ]);

            // Handle translations if provided
            if (! empty($validated['translations'])) {
                foreach ($validated['translations'] as $locale => $data) {
                    if (isset($data['name'])) {
                        $page->setTranslation('name', $locale, $data['name']);
                    }
                }
                if ($page->status === 'published') {
                    $page->published_at = now();
                }
                $page->save();
            }

            return Response::json([
                'id' => $page->id,
                'name' => $page->name,
                'slug' => $page->slug,
                'status' => $page->status,
            ]);
        } catch (\Exception $e) {
            return Response::text($e->getMessage());
        }
    }
}

<?php

namespace SmartCms\Kit\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use SmartCms\Kit\Models\Page;

class UpdatePage extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'update_page';

    /**
     * The tool's description.
     */
    protected string $description = 'Update page metadata (title, slug, status, etc.)';

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Page ID to update')
                ->required(),
            'name' => $schema->string()
                ->description('New page name'),
            'slug' => $schema->string()
                ->description('New page slug'),
            'status' => $schema->string()
                ->enum(['scheduled', 'draft', 'published'])
                ->description('Page status'),
            'language' => $schema->string()
                ->description('Language for translation updates'),
        ];
    }

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:pages,id',
            'name' => 'nullable|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pages')->ignore($request->integer('id')),
            ],
            'status' => 'nullable|string|in:scheduled,draft,published',
            'language' => 'nullable|string',
        ]);

        try {
            $page = Page::findOrFail($validated['id']);

            // Update fields if provided
            if (isset($validated['name'])) {
                if (! empty($validated['language'])) {
                    $page->setTranslation('name', $validated['language'], $validated['name']);
                } else {
                    $page->name = $validated['name'];
                }
            }

            if (isset($validated['slug'])) {
                $page->slug = $validated['slug'];
            }

            if (isset($validated['status'])) {
                $page->status = $validated['status'];
            }

            $page->save();

            return Response::json([
                'id' => $page->id,
                'name' => $page->name,
                'slug' => $page->slug,
                'status' => $page->status,
                'updated_at' => $page->updated_at?->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return Response::text('Error updating page: ' . $e->getMessage());
        }
    }
}

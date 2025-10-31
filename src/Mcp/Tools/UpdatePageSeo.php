<?php

namespace SmartCms\Kit\Mcp\Tools;

use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use SmartCms\Kit\Models\Page;

class UpdatePageSeo extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'update_page_seo';

    /**
     * The tool's description.
     */
    protected string $description = 'Update SEO settings for a page (meta title, description, keywords, OG tags)';

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Page ID to update')
                ->required(),
            'meta_title' => $schema->string()
                ->description('Meta title for SEO'),
            'meta_description' => $schema->string()
                ->description('Meta description for SEO'),
            // 'meta_keywords' => $schema->string()
            //     ->description('Meta keywords (comma-separated)'),
            'heading' => $schema->string()
                ->description('Heading for SEO'),
            'summary' => $schema->string()
                ->description('Summary for SEO'),
            'content' => $schema->string()
                ->description('Content for SEO'),
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
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            // 'meta_keywords' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'summary' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'language' => 'nullable|string',
        ]);

        try {
            $page = Page::findOrFail($validated['id']);
            $language = $validated['language'] ?? current_lang();
            // Update SEO fields if provided
            if (isset($validated['meta_title'])) {
                $page->setTranslation('title', $language, $validated['meta_title']);
            }

            if (isset($validated['meta_description'])) {
                $page->setTranslation('description', $language, $validated['meta_description']);
            }

            // if (isset($validated['meta_keywords'])) {
            //     $page->setTranslation('keywords', $language, $validated['meta_keywords']);
            // }

            if (isset($validated['heading'])) {
                $page->setTranslation('heading', $language, $validated['heading']);
            }

            if (isset($validated['summary'])) {
                $page->setTranslation('summary', $language, $validated['summary']);
            }

            if (isset($validated['content'])) {
                $page->setTranslation('content', $language, $validated['content']);
            }

            $page->save();

            return Response::text('Page SEO updated successfully');
        } catch (\Exception $e) {
            return Response::text('Error updating page SEO: ' . $e->getMessage());
        }
    }
}

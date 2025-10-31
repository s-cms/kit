<?php

namespace SmartCms\Kit\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Illuminate\JsonSchema\JsonSchema;
use SmartCms\Kit\Models\Page;

#[IsReadOnly]
class GetPageBlocks extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'get_page_blocks';

    /**
     * The tool's description.
     */
    protected string $description = 'Get all blocks attached to a page in order with their data';

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Page ID')
                ->required(),
            'language' => $schema->string()
                ->description('Language code for block data'),
        ];
    }

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:pages,id',
            'language' => 'nullable|string',
        ]);

        $page = Page::with(['blocks'])->find($validated['id']);

        if (! $page) {
            return Response::text('Page not found');
        }
        $language = $validated['language'] ?? main_lang();

        $blocks = $page->blocks->map(function ($block) use ($language) {
            $data = [
                'id' => $block->id,
                'type' => $block->type,
                'title' => $block->title,
                'status' => $block->status,
            ];

            $data['data'] = $block->getTranslation('data', $language, false);

            return $data;
        })->values();

        return Response::json($blocks);
    }
}

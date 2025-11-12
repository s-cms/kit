<?php

namespace SmartCms\Kit\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;

class UnpublishPage extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'unpublish_page';

    /**
     * The tool's description.
     */
    protected string $description = 'Set page status to draft (unpublished)';

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Page ID to unpublish')
                ->required(),
        ];
    }

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:pages,id',
        ]);

        try {
            $page = Page::findOrFail($validated['id']);
            $page->status = PageStatus::Draft;
            $page->published_at = null;
            $page->save();

            return Response::text('Page ' . $page->name . ' unpublished successfully');
        } catch (\Exception $e) {
            return Response::text('Error unpublishing page: ' . $e->getMessage());
        }
    }
}

<?php

namespace SmartCms\Kit\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Illuminate\JsonSchema\JsonSchema;
use SmartCms\Kit\Models\Page;

class DeletePage extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'delete_page';

    /**
     * The tool's description.
     */
    protected string $description = 'Delete a page (use with caution in development)';

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Page ID to delete')
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
            $pageTitle = $page->name;
            $page->delete();

            return Response::text('Page "' . $pageTitle . '" deleted successfully');
        } catch (\Exception $e) {
            return Response::text('Error deleting page: ' . $e->getMessage());
        }
    }
}

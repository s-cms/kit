<?php

namespace SmartCms\Kit\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Illuminate\JsonSchema\JsonSchema;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;

class PublishPage extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'publish_page';

    /**
     * The tool's description.
     */
    protected string $description = 'Set page status to published';

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Page ID to publish')
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
            $page->status = PageStatus::Published->value;
            $page->published_at = now();
            $page->save();

            return Response::text('Page ' . $page->name . ' published successfully');
        } catch (\Exception $e) {
            return Response::text('Error publishing page: ' . $e->getMessage());
        }
    }
}

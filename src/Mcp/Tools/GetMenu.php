<?php

namespace SmartCms\Kit\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use SmartCms\Menu\Models\Menu;

#[IsReadOnly]
class GetMenu extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'get_menu';

    /**
     * The tool's description.
     */
    protected string $description = 'Get specific menu with full item tree structure';

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Menu ID'),
            'name' => $schema->string()
                ->description('Menu name (alternative to id)'),
        ];
    }

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'name' => 'nullable|string',
        ]);

        if (empty($validated['id']) && empty($validated['name'])) {
            return Response::text('Either id or name must be provided');
        }

        // Find menu
        $query = Menu::query();
        if (! empty($validated['id'])) {
            $menu = $query->find($validated['id']);
        } else {
            $menu = $query->where('name', 'like', '%' . $validated['name'] . '%')->first();
        }

        if (! $menu) {
            return Response::text('Menu not found');
        }

        $result = [
            'id' => $menu->id,
            'name' => $menu->name,
            'items' => $menu->items ?? [],
        ];

        return Response::text(json_encode($result, JSON_PRETTY_PRINT));
    }
}

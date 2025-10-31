<?php

namespace SmartCms\Kit\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use SmartCms\Menu\Models\Menu;

#[IsReadOnly]
class GetMenus extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'get_menus';

    /**
     * The tool's description.
     */
    protected string $description = 'Get all menus with basic information';

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        $menus = Menu::query()->get();

        $result = $menus->map(function ($menu) {
            return [
                'id' => $menu->id,
                'name' => $menu->name,
                'items_count' => is_array($menu->items) ? count($menu->items) : 0,
            ];
        });

        return Response::text(json_encode([
            'count' => $result->count(),
            'menus' => $result,
        ], JSON_PRETTY_PRINT));
    }
}

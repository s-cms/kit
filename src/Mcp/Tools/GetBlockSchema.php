<?php

namespace SmartCms\Kit\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Illuminate\JsonSchema\JsonSchema;
use SmartCms\Kit\Services\Block\BlockService;

#[IsReadOnly]
class GetBlockSchema extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'get_block_schema';

    /**
     * The tool's description.
     */
    protected string $description = 'Get detailed schema for a specific block type';

    public function __construct(
        protected BlockService $blockService
    ) {}

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->description('Block type id (e.g., "HeaderSection")')
                ->required(),
        ];
    }

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'type' => 'required|string',
        ]);

        $schema = $this->blockService->blocks->firstWhere('id', $validated['type']);

        if (! $schema) {
            return Response::text("Block type '{$validated['type']}' not found");
        }

        return Response::json($schema);
    }
}

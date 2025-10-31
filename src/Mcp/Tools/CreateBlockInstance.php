<?php

namespace SmartCms\Kit\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Services\Block\BlockDataMerger;
use SmartCms\Kit\Services\Block\BlockService;

class CreateBlockInstance extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'create_block_instance';

    /**
     * The tool's description.
     */
    protected string $description = 'Create a new block instance with schema defaults';

    public function __construct(
        protected BlockService $blockService,
        protected BlockDataMerger $merger
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
            'title' => $schema->string()
                ->description('Block title'),
            'data' => $schema->object()
                ->description('Initial block data (will be merged with defaults)'),
            'status' => $schema->boolean()
                ->description('Block status (true=active, false=inactive)')
                ->default(true),
        ];
    }

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'title' => 'nullable|string|max:255',
            'data' => 'nullable|array',
            'status' => 'boolean',
        ]);

        try {
            // Find schema for this block type
            $schema = $this->blockService->blocks->firstWhere('id', $validated['type']);

            if (! $schema) {
                return Response::text("Block type '{$validated['type']}' not found");
            }

            // Get languages
            $languages = app('lang')->adminLanguages()->pluck('slug')->toArray();

            // Merge provided data with schema defaults
            $data = $validated['data'] ?? [];
            $mergedData = $this->merger->merge($data, $schema, false, $languages);

            // Create block
            $block = Block::create([
                'type' => $validated['type'],
                'title' => $validated['title'] ?? $validated['type'],
                'schema' => $schema,
                'data' => $mergedData,
                'status' => $validated['status'] ?? true,
            ]);

            return Response::json($block);
        } catch (\Exception $e) {
            return Response::text('Error creating block: ' . $e->getMessage());
        }
    }
}

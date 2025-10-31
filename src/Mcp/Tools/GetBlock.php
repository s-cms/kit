<?php

namespace SmartCms\Kit\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Illuminate\JsonSchema\JsonSchema;
use SmartCms\Kit\Models\Block;

#[IsReadOnly]
class GetBlock extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'get_block';

    /**
     * The tool's description.
     */
    protected string $description = 'Get specific block instance with its data and schema';

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Block ID')
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
            'id' => 'required|integer|exists:blocks,id',
            'language' => 'nullable|string',
        ]);

        $block = Block::find($validated['id']);

        if (! $block) {
            return Response::text('Block not found');
        }

        $result = [
            'id' => $block->id,
            'type' => $block->type,
            'title' => $block->title,
            'status' => $block->status,
            'schema' => $block->schema,
            'created_at' => $block->created_at?->toIso8601String(),
            'updated_at' => $block->updated_at?->toIso8601String(),
        ];

        if (! empty($validated['language'])) {
            $result['data'] = $block->getTranslation('data', $validated['language'], false);
        } else {
            $result['data'] = $block->getTranslations('data');
        }

        return Response::json($result);
    }
}

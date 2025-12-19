<?php

namespace SmartCms\Kit\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use SmartCms\Kit\Models\Block;

#[IsReadOnly]
class ReviewBlockData extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'review_block_data';

    /**
     * The tool's description.
     */
    protected string $description = 'Review block data with transformed custom variable types applied';

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
                ->description('Language code for block data (optional, defaults to all locales)'),
            'include_raw' => $schema->boolean()
                ->description('Include raw (untransformed) data for comparison'),
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
            'include_raw' => 'nullable|boolean',
        ]);

        $block = Block::find($validated['id']);

        if (!$block) {
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

        // Get transformed data
        if (!empty($validated['language'])) {
            // Single language
            $result['transformed_data'] = $block->getTransformedData($validated['language']);

            if (!empty($validated['include_raw'])) {
                $result['raw_data'] = $block->getTranslation('data', $validated['language'], false);
            }
        } else {
            // All locales
            $result['transformed_data'] = $block->getTransformedDataForAllLocales();

            if (!empty($validated['include_raw'])) {
                $result['raw_data'] = $block->getTranslations('data');
            }
        }

        return Response::json($result);
    }
}

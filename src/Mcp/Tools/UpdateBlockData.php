<?php

namespace SmartCms\Kit\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Illuminate\JsonSchema\JsonSchema;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Services\Block\BlockDataMerger;

class UpdateBlockData extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'update_block_data';

    /**
     * The tool's description.
     */
    protected string $description = 'Update block content with schema validation and intelligent merging';

    public function __construct(
        protected BlockDataMerger $merger
    ) {}

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Block ID to update')
                ->required(),
            'data' => $schema->object()
                ->description('New data for the block')
                ->required(),
            'language' => $schema->string()
                ->description('Language code for the update'),
            'merge' => $schema->boolean()
                ->description('Merge with existing data (true) or replace completely (false)')
                ->default(true),
        ];
    }

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:blocks,id',
            'data' => 'required|array',
            'language' => 'nullable|string',
            'merge' => 'boolean',
        ]);

        try {
            $block = Block::findOrFail($validated['id']);
            $newData = $validated['data'];
            $merge = $validated['merge'] ?? true;

            if ($merge) {
                // Get current data
                if (! empty($validated['language'])) {
                    $currentData = $block->getTranslation('data', $validated['language'], false) ?? [];
                } else {
                    $currentData = $block->getTranslations('data') ?? [];
                }

                // Merge new data with schema validation
                if (! empty($validated['language'])) {
                    // Single language merge
                    $mergedData = $this->merger->merge(
                        array_merge($currentData, $newData),
                        $block->schema,
                        false,
                        [$validated['language']]
                    );
                    $block->setTranslation('data', $validated['language'], $mergedData[$validated['language']]);
                } else {
                    // All languages merge
                    $languages = app()->has('lang')
                        ? app('lang')->adminLanguages()->pluck('slug')->toArray()
                        : ['en'];

                    foreach ($languages as $lang) {
                        $langCurrentData = $currentData[$lang] ?? [];
                        $langNewData = $newData[$lang] ?? $newData;
                        $mergedLangData = array_merge($langCurrentData, $langNewData);

                        $mergedData = $this->merger->merge(
                            [$lang => $mergedLangData],
                            $block->schema,
                            false,
                            [$lang]
                        );
                        $block->setTranslation('data', $lang, $mergedData[$lang]);
                    }
                }
            } else {
                // Replace mode - validate against schema
                if (! empty($validated['language'])) {
                    $mergedData = $this->merger->merge(
                        $newData,
                        $block->schema,
                        false,
                        [$validated['language']]
                    );
                    $block->setTranslation('data', $validated['language'], $mergedData[$validated['language']]);
                } else {
                    $block->data = $newData;
                }
            }

            $block->save();

            return Response::json([
                [
                    'type' => 'text',
                    'text' => json_encode([
                        'success' => true,
                        'message' => 'Block data updated successfully',
                        'block' => [
                            'id' => $block->id,
                            'type' => $block->type,
                            'title' => $block->title,
                            'updated_at' => $block->updated_at?->toIso8601String(),
                        ],
                    ], JSON_PRETTY_PRINT),
                ],
            ]);
        } catch (\Exception $e) {
            return Response::json([
                [
                    'type' => 'text',
                    'text' => json_encode([
                        'success' => false,
                        'error' => $e->getMessage(),
                        'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                    ], JSON_PRETTY_PRINT),
                ],
            ]);
        }
    }
}

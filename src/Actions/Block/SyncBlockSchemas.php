<?php

namespace SmartCms\Kit\Actions\Block;

use Illuminate\Support\Facades\Log;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Services\Block\BlockDataMerger;
use SmartCms\Kit\Services\Block\BlockService;

class SyncBlockSchemas
{
    /**
     * Sync all blocks with current schema definitions
     *
     * @param  bool  $removeOrphans  Whether to remove fields not in schema
     * @return array Statistics about the sync operation
     */
    public static function run(bool $removeOrphans = false): array
    {
        $merger = app(BlockDataMerger::class);
        $blockService = app(BlockService::class);

        Log::info('Starting block schema sync');

        // Get all available languages
        $languages = app('lang')->adminLanguages()->pluck('slug')->toArray();

        // Load current schemas
        $allSchemas = $blockService->blocks;

        // Load all blocks
        $blocks = Block::all();

        // Find schemas that don't have blocks in the database and create them
        $existingTypes = $blocks->pluck('type')->unique();
        $created = 0;

        foreach ($allSchemas as $schemaData) {
            $schemaId = $schemaData['id'] ?? null;

            if (! $schemaId || $existingTypes->contains($schemaId)) {
                continue;
            }

            try {
                // Create default data from schema for all languages
                $defaultData = $merger->merge([], $schemaData, false, $languages);

                Block::create([
                    'type' => $schemaId,
                    'title' => $schemaId,
                    'schema' => $schemaData,
                    'status' => true,
                    'data' => $defaultData,
                ]);

                $created++;
                Log::info("Created new block for schema '{$schemaId}' with data for languages: " . implode(', ', $languages));
            } catch (\Exception $e) {
                Log::error("Error creating block for schema '{$schemaId}': " . $e->getMessage());
            }
        }

        // Reload blocks after creation
        if ($created > 0) {
            $blocks = Block::all();
        }

        if ($blocks->isEmpty()) {
            return [
                'success' => true,
                'created' => $created,
                'updated' => 0,
                'unchanged' => 0,
                'deleted' => 0,
                'errors' => 0,
                'message' => $created > 0
                    ? "Created {$created} new block(s)"
                    : 'No blocks found to sync',
            ];
        }

        $updated = 0;
        $unchanged = 0;
        $deleted = 0;
        $errors = 0;
        $errorMessages = [];

        foreach ($blocks as $block) {
            try {
                // Find the schema for this block type
                $schemaData = $allSchemas->firstWhere('id', $block->type);

                if (! $schemaData) {
                    // Block type doesn't exist in schemas - delete it
                    Log::info("Deleting orphaned block #{$block->id} with type '{$block->type}' (schema not found)");
                    // $block->delete();
                    // $deleted++;

                    continue;
                }

                // Skip if schema hasn't changed - no need to update
                if ($schemaData == $block->schema) {
                    $unchanged++;

                    continue;
                }

                // Get current stored data - ensure it's an array
                $storedData = $block->getTranslations('data') ?? [];

                // Safety check: ensure storedData is an array (could be empty string from old data)
                if (! is_array($storedData)) {
                    $storedData = [];
                }

                // Merge with schema defaults for all languages
                $mergedData = $merger->merge($storedData, $schemaData, $removeOrphans, $languages);

                // Update block with merged data and new schema
                $block->data = $mergedData;
                $block->schema = $schemaData;
                $block->save();
                $updated++;
            } catch (\Exception $e) {
                $errorMessages[] = "Block #{$block->id}: " . $e->getMessage();
                $errors++;
                Log::error("Error syncing block #{$block->id}: " . $e->getMessage());
            }
        }

        $message = "Synced {$blocks->count()} block(s): {$created} created, {$updated} updated, {$unchanged} unchanged, {$deleted} deleted";

        if ($errors > 0) {
            $message .= ", {$errors} error(s)";
        }

        return [
            'success' => $errors === 0,
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'deleted' => $deleted,
            'errors' => $errors,
            'error_messages' => $errorMessages,
            'message' => $message,
        ];
    }
}

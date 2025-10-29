<?php

namespace SmartCms\Kit\Commands;

use Illuminate\Console\Command;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Services\Block\BlockDataMerger;
use SmartCms\Kit\Services\Block\BlockService;

class SyncBlockSchemas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blocks:sync-schemas
                            {--remove-orphans : Remove fields that no longer exist in schema}
                            {--dry-run : Show what would be changed without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all blocks with current schema definitions, adding missing defaults';

    /**
     * Execute the console command.
     */
    public function handle(BlockService $blockService, BlockDataMerger $merger): int
    {
        $dryRun = $this->option('dry-run');
        $removeOrphans = $this->option('remove-orphans');

        $this->info('🔄 Syncing block schemas...');

        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode - no changes will be saved');
        }

        // Get all available languages
        $languages = app('lang')->adminLanguages()->pluck('slug')->toArray();
        $this->info("Languages: " . implode(', ', $languages));

        // Load all blocks
        $blocks = Block::all();

        if ($blocks->isEmpty()) {
            $this->info('No blocks found to sync.');
            return self::SUCCESS;
        }

        $this->info("Found {$blocks->count()} block(s) to process");

        // Load current schemas
        $allSchemas = $blockService->blocks;

        $updated = 0;
        $unchanged = 0;
        $errors = 0;

        foreach ($blocks as $block) {
            try {
                // Find the schema for this block type
                $schemaData = $allSchemas->firstWhere('id', $block->type);

                if (!$schemaData) {
                    $this->warn("⚠ Block #{$block->id} ({$block->title}): Schema type '{$block->type}' not found - skipping");
                    $errors++;
                    continue;
                }

                // Get current stored data - ensure it's an array
                $storedData = $block->data ?? [];

                // Safety check: ensure storedData is an array (could be empty string from old data)
                if (!is_array($storedData)) {
                    $storedData = [];
                }

                // Merge with schema defaults for all languages
                $mergedData = $merger->merge($storedData, $schemaData, $removeOrphans, $languages);

                // Check if data changed
                if ($this->dataChanged($storedData, $mergedData)) {
                    $this->line("✓ Block #{$block->id} ({$block->title}): Updated with new schema defaults");

                    if (!$dryRun) {
                        $block->data = $mergedData;
                        $block->save();
                    }

                    $updated++;

                    // Show diff in verbose mode
                    if ($this->output->isVerbose()) {
                        $this->showDiff($storedData, $mergedData);
                    }
                } else {
                    $this->line("  Block #{$block->id} ({$block->title}): No changes needed");
                    $unchanged++;
                }
            } catch (\Exception $e) {
                $this->error("❌ Block #{$block->id}: Error - " . $e->getMessage());
                $errors++;
            }
        }

        // Summary
        $this->newLine();
        $this->info('📊 Summary:');
        $this->line("  Updated: {$updated}");
        $this->line("  Unchanged: {$unchanged}");

        if ($errors > 0) {
            $this->line("  Errors: {$errors}");
        }

        if ($dryRun && $updated > 0) {
            $this->newLine();
            $this->warn('⚠ This was a dry-run. Run without --dry-run to apply changes.');
        }

        return self::SUCCESS;
    }

    /**
     * Check if data has changed
     */
    protected function dataChanged(array $old, array $new): bool
    {
        return json_encode($old) !== json_encode($new);
    }

    /**
     * Show diff between old and new data
     */
    protected function showDiff(array $old, array $new): void
    {
        $oldKeys = array_keys($old);
        $newKeys = array_keys($new);

        $added = array_diff($newKeys, $oldKeys);
        $removed = array_diff($oldKeys, $newKeys);

        if (!empty($added)) {
            $this->line('    Added fields: ' . implode(', ', $added));
        }

        if (!empty($removed)) {
            $this->line('    Removed fields: ' . implode(', ', $removed));
        }
    }
}

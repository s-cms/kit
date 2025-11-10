<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('kit.pages_table_name');

        // Add new columns
        Schema::table($tableName, function (Blueprint $table) {
            $table->string('type', 50)->default('page')->after('slug');
            $table->json('metadata')->nullable()->after('settings');
            $table->index('type');
        });

        // Migrate existing data
        $this->migrateExistingData($tableName);
    }

    public function down(): void
    {
        $tableName = config('kit.pages_table_name');

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn(['type', 'metadata']);
        });
    }

    protected function migrateExistingData(string $tableName): void
    {
        // SQLite compatibility - handle JSON extraction differently
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite migration
            DB::statement("
                UPDATE {$tableName}
                SET type = CASE
                    WHEN is_root = 1 THEN 'category'
                    WHEN is_root = 0 AND parent_id IS NULL AND root_id IS NULL THEN 'page'
                    WHEN is_root = 0 AND root_id IS NOT NULL AND parent_id = root_id THEN
                        CASE
                            WHEN json_extract((SELECT settings FROM {$tableName} p WHERE p.id = {$tableName}.root_id), '$.is_categories') = 'true'
                            THEN 'category'
                            ELSE 'page'
                        END
                    ELSE 'page'
                END
            ");
        } elseif ($driver === 'mysql') {
            // MySQL migration
            DB::statement("
                UPDATE {$tableName}
                SET type = CASE
                    WHEN is_root = 1 THEN 'category'
                    WHEN is_root = 0 AND parent_id IS NULL AND root_id IS NULL THEN 'page'
                    WHEN is_root = 0 AND root_id IS NOT NULL AND parent_id = root_id THEN
                        CASE
                            WHEN (SELECT JSON_EXTRACT(settings, '$.is_categories')
                                  FROM {$tableName} p WHERE p.id = {$tableName}.root_id) = TRUE
                            THEN 'category'
                            ELSE 'page'
                        END
                    ELSE 'page'
                END
            ");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL migration
            DB::statement("
                UPDATE {$tableName}
                SET type = CASE
                    WHEN is_root = true THEN 'category'
                    WHEN is_root = false AND parent_id IS NULL AND root_id IS NULL THEN 'page'
                    WHEN is_root = false AND root_id IS NOT NULL AND parent_id = root_id THEN
                        CASE
                            WHEN (SELECT (settings->>'is_categories')::boolean
                                  FROM {$tableName} p WHERE p.id = {$tableName}.root_id) = true
                            THEN 'category'
                            ELSE 'page'
                        END
                    ELSE 'page'
                END
            ");
        }

        // Migrate settings to metadata
        DB::table($tableName)->update([
            'metadata' => DB::raw('settings'),
        ]);
    }
};

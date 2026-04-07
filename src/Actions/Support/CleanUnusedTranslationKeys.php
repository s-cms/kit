<?php

namespace SmartCms\Kit\Actions\Support;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Models\Page;

class CleanUnusedTranslationKeys
{
    use AsAction;

    /**
     * @param  array<int, string>  $keepLanguages  Language slugs to keep
     */
    public function handle(array $keepLanguages): int
    {
        $totalUpdated = 0;

        foreach ($this->getTranslatableModels() as $model) {
            $totalUpdated += $this->cleanInModel($model, $keepLanguages);
        }

        return $totalUpdated;
    }

    /**
     * Check if any model has translation keys not in the active languages list.
     *
     * @param  array<int, string>  $activeLanguages
     */
    public function hasUnusedKeys(array $activeLanguages): bool
    {
        foreach ($this->getTranslatableModels() as $model) {
            $translatableFields = $model->translatable ?? $model->getTranslatableAttributes();
            $firstField = $translatableFields[0] ?? null;

            if (! $firstField) {
                continue;
            }

            $found = false;

            $model::query()
                ->whereNotNull($firstField)
                ->chunkById(100, function ($records) use ($translatableFields, $activeLanguages, &$found) {
                    foreach ($records as $record) {
                        foreach ($translatableFields as $field) {
                            $translations = $record->getTranslations($field);
                            $extraKeys = array_diff(array_keys($translations), $activeLanguages);

                            if (! empty($extraKeys)) {
                                $found = true;

                                return false; // Stop chunking
                            }
                        }
                    }
                });

            if ($found) {
                return true;
            }
        }

        return false;
    }

    protected function cleanInModel(Model $model, array $keepLanguages): int
    {
        $translatableFields = $model->translatable ?? $model->getTranslatableAttributes();
        $updated = 0;

        $model::query()->chunkById(100, function ($records) use ($translatableFields, $keepLanguages, &$updated) {
            foreach ($records as $record) {
                $changed = false;

                foreach ($translatableFields as $field) {
                    $translations = $record->getTranslations($field);

                    if (empty($translations)) {
                        continue;
                    }

                    $filtered = array_intersect_key($translations, array_flip($keepLanguages));

                    if (count($filtered) !== count($translations)) {
                        $record->replaceTranslations($field, $filtered);
                        $changed = true;
                    }
                }

                if ($changed) {
                    $record->saveQuietly();
                    $updated++;
                }
            }
        });

        return $updated;
    }

    /**
     * @return array<int, Model>
     */
    protected function getTranslatableModels(): array
    {
        $models = [new Page, new Block];

        foreach (config('kit.translatable_models', []) as $modelClass) {
            try {
                $models[] = new $modelClass;
            } catch (\Throwable) {
                // Skip models that cannot be instantiated
            }
        }

        return $models;
    }
}

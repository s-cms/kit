<?php

namespace SmartCms\Kit\Actions\Support;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Models\Page;

class RenameTranslationKey
{
    use AsAction;

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

    public function handle(string $oldLang, string $newLang): int
    {
        $totalUpdated = 0;

        foreach ($this->getTranslatableModels() as $model) {
            $totalUpdated += $this->renameInModel($model, $oldLang, $newLang);
        }

        return $totalUpdated;
    }

    protected function renameInModel(Model $model, string $oldLang, string $newLang): int
    {
        $translatableFields = $model->translatable ?? $model->getTranslatableAttributes();
        $updated = 0;

        $model::query()->chunkById(100, function ($records) use ($translatableFields, $oldLang, $newLang, &$updated) {
            foreach ($records as $record) {
                $changed = false;

                foreach ($translatableFields as $field) {
                    $translations = $record->getTranslations($field);

                    if (! array_key_exists($oldLang, $translations)) {
                        continue;
                    }

                    if (array_key_exists($newLang, $translations)) {
                        continue;
                    }

                    $translations[$newLang] = $translations[$oldLang];
                    unset($translations[$oldLang]);

                    $record->replaceTranslations($field, $translations);
                    $changed = true;
                }

                if ($changed) {
                    $record->saveQuietly();
                    $updated++;
                }
            }
        });

        return $updated;
    }

    public function needsRename(string $oldLang, string $newLang): bool
    {
        return $this->hasKeyInAnyModel($oldLang) && ! $this->hasKeyInAnyModel($newLang);
    }

    protected function hasKeyInAnyModel(string $lang): bool
    {
        foreach ($this->getTranslatableModels() as $model) {
            $translatableFields = $model->translatable ?? $model->getTranslatableAttributes();

            foreach ($translatableFields as $field) {
                try {
                    $exists = $model::query()
                        ->whereNotNull($field)
                        ->whereLocale($field, $lang)
                        ->exists();
                } catch (\Throwable) {
                    // Skip fields that may have malformed JSON (e.g. empty arrays in SQLite)
                    continue;
                }

                if ($exists) {
                    return true;
                }
            }
        }

        return false;
    }
}

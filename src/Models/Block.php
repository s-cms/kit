<?php

namespace SmartCms\Kit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Log;
use SmartCms\TemplateBuilder\Support\VariableTypeRegistry;
use Spatie\Translatable\HasTranslations;

class Block extends Model
{
    use HasTranslations;

    protected $guarded = [];

    protected array $translatable = [
        'data',
    ];

    protected $casts = [
        'status' => 'boolean',
        'data' => 'array',
        'schema' => 'array',
    ];

    public function getTable()
    {
        return config('kit.blocks_table_name');
    }

    /**
     * Get block data with custom variable types transformed
     *
     * This method processes the block's data through the VariableTypeRegistry
     * to transform custom variable types (like emails, phones, etc.) into
     * their final values for rendering.
     *
     * @param  string|null  $locale  Optional locale code for multilingual blocks
     * @return array Transformed block data
     */
    public function getTransformedData(?string $locale = null): array
    {
        $registry = app(VariableTypeRegistry::class);

        // Get raw data for the specified locale
        $data = $locale ? $this->getTranslation('data', $locale) : $this->data;

        // If no schema is defined, return data as-is
        if (empty($this->schema)) {
            return $data ?? [];
        }

        // Get schema properties
        $properties = $this->schema['properties'] ?? [];

        if (empty($properties)) {
            return $data ?? [];
        }

        $transformedData = [];

        // Process each field in the schema
        foreach ($properties as $fieldName => $fieldSchema) {
            $fieldValue = $data[$fieldName] ?? null;

            // Check if this field uses a custom variable type
            $variableType = $this->getVariableTypeForField($fieldSchema, $registry);

            if ($variableType) {
                // Transform the value using the variable type
                $transformedData[$fieldName] = $this->transformFieldValue(
                    $fieldName,
                    $fieldValue,
                    $variableType,
                    $fieldSchema
                );
            } else {
                // No custom type, keep original value
                $transformedData[$fieldName] = $fieldValue;
            }
        }

        return $transformedData;
    }

    /**
     * Get the variable type instance for a field if it exists
     *
     * @param  array  $fieldSchema  Field schema definition
     * @param  VariableTypeRegistry  $registry  Variable type registry
     * @return mixed Variable type instance or null
     */
    protected function getVariableTypeForField(array $fieldSchema, VariableTypeRegistry $registry): mixed
    {
        // Priority 1: Check inputType
        $inputType = $fieldSchema['inputType'] ?? null;
        if ($inputType && $variableType = $registry->get($inputType)) {
            return $variableType;
        }

        // Priority 2: Check standard type
        $type = $fieldSchema['type'] ?? null;
        if ($type && $variableType = $registry->get($type)) {
            return $variableType;
        }

        return null;
    }

    /**
     * Transform a field value using its variable type
     *
     * @param  string  $fieldName  Field name
     * @param  mixed  $value  Field value
     * @param  mixed  $variableType  Variable type instance
     * @param  array  $fieldSchema  Field schema definition
     * @return mixed Transformed value
     */
    protected function transformFieldValue(string $fieldName, mixed $value, mixed $variableType, array $fieldSchema): mixed
    {
        // If value is null, return the default value from the variable type
        if ($value === null) {
            return $variableType->getDefaultValue();
        }

        // Transform the value using the variable type's getValue method
        try {
            return $variableType->getValue($value);
        } catch (\Exception $e) {
            Log::warning("Failed to transform block field '{$fieldName}' in block {$this->id}: {$e->getMessage()}");

            return $variableType->getDefaultValue();
        }
    }

    /**
     * Get transformed data for all locales
     *
     * @return array Associative array with locale codes as keys
     */
    public function getTransformedDataForAllLocales(): array
    {
        $locales = $this->getTranslatedLocales('data');
        $transformedData = [];

        foreach ($locales as $locale) {
            $transformedData[$locale] = $this->getTransformedData($locale);
        }

        return $transformedData;
    }

    /**
     * Helper method to get transformed data for the current app locale
     *
     * @return array Transformed block data
     */
    public function transformedData(): array
    {
        return $this->getTransformedData(app()->getLocale());
    }

    /**
     * Get all blockable relationships (pivot records).
     * This allows accessing any entity type that has this block attached.
     */
    public function blockables(): HasMany
    {
        return $this->hasMany(Blockable::class, 'block_id', 'id');
    }

    /**
     * Get only active blockable relationships.
     */
    public function activeBlockables(): HasMany
    {
        return $this->blockables()->active();
    }

    /**
     * Get all pages that have this block attached.
     * This is the inverse polymorphic relationship for Filament's RelationManager.
     */
    public function pages(): MorphToMany
    {
        return $this->morphedByMany(
            Page::class,
            'blockable',
            'blockables'
        )
            ->using(Blockable::class)
            ->withPivot(['status', 'show_from', 'show_until', 'sorting'])
            ->withTimestamps()
            ->orderBy('sorting');
    }
}

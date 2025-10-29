<?php

namespace SmartCms\Kit\Services\Block;

use SmartCms\TemplateBuilder\Support\VariableTypeRegistry;

/**
 * BlockDataMerger
 *
 * Intelligently merges stored block data with current schema defaults.
 * Preserves user-entered values while adding new fields with their defaults.
 *
 * Features:
 * - Preserves all user data (never overwrites)
 * - Adds missing fields with defaults from schema
 * - Handles nested objects recursively
 * - Merges arrays intelligently (adds missing fields to existing items)
 * - Removes fields that no longer exist in schema (optional)
 *
 * @example
 * ```php
 * $merger = new BlockDataMerger();
 * $mergedData = $merger->merge($storedData, $schemaJsonData);
 * ```
 */
class BlockDataMerger
{
    public function __construct(private VariableTypeRegistry $registry) {}

    /**
     * Merge stored data with schema defaults
     *
     * @param  array  $storedData  The data currently stored in the block
     * @param  array  $schema  The JSON schema (OpenAPI 3.0 format from Zod)
     * @param  bool  $removeOrphans  Whether to remove fields not in schema
     * @param  array|null  $languages  List of language codes to support (e.g., ['en', 'uk'])
     * @return array Merged data with all defaults filled in
     */
    public function merge(array $storedData, array $schema, bool $removeOrphans = false, ?array $languages = null): array
    {
        // If languages are provided, merge for each language
        if (is_null($languages) || empty($languages)) {
            return $this->mergeSingleLanguage($storedData, $schema, $removeOrphans);
        }

        $mergedData = [];
        foreach ($languages as $langCode) {
            $langData = $storedData[$langCode] ?? [];

            $mergedData[$langCode] = $this->mergeSingleLanguage($langData, $schema, $removeOrphans);
        }

        return $mergedData;
    }

    /**
     * Merge data for a single language
     *
     * @param  array  $storedData  The data for this language
     * @param  array  $schema  The JSON schema
     * @param  bool  $removeOrphans  Whether to remove fields not in schema
     * @return array Merged data
     */
    protected function mergeSingleLanguage(array $storedData, array $schema, bool $removeOrphans = false): array
    {
        // If stored data is empty and schema has a root-level default, use it as the base
        if (empty($storedData) && isset($schema['default']) && is_array($schema['default'])) {
            $storedData = $schema['default'];
        }

        // Handle root-level array schemas (like AdvantagesSection)
        if (isset($schema['type']) && $schema['type'] === 'array') {
            return $this->mergeArray($storedData, $schema, $removeOrphans);
        }

        // Handle object schemas
        if (isset($schema['properties'])) {
            return $this->mergeObject($storedData, $schema, $removeOrphans);
        }

        // If no properties, return stored data as-is
        return $storedData;
    }

    /**
     * Merge object data with schema
     *
     * @param  array  $storedData  Stored object data
     * @param  array  $schema  Object schema with properties
     * @param  bool  $removeOrphans  Whether to remove fields not in schema
     * @return array Merged object
     */
    protected function mergeObject(array $storedData, array $schema, bool $removeOrphans = false): array
    {
        $properties = $schema['properties'] ?? [];
        $merged = [];
        // Add all schema fields with defaults or preserved values
        foreach ($properties as $fieldName => $fieldSchema) {
            if (array_key_exists($fieldName, $storedData)) {
                // Field exists in stored data - preserve it but potentially merge nested data
                $merged[$fieldName] = $this->mergeField($storedData[$fieldName], $fieldSchema, $removeOrphans);
            } else {
                // Field is new - add default value
                $merged[$fieldName] = $this->getDefaultValue($fieldSchema);
            }
        }

        // Optionally keep fields that don't exist in schema anymore
        if (! $removeOrphans) {
            foreach ($storedData as $key => $value) {
                if (! array_key_exists($key, $properties)) {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }

    /**
     * Merge array data with schema
     *
     * @param  array  $storedData  Stored array data
     * @param  array  $schema  Array schema with items definition
     * @param  bool  $removeOrphans  Whether to remove fields not in schema
     * @return array Merged array
     */
    protected function mergeArray(array $storedData, array $schema, bool $removeOrphans = false): array
    {
        $itemSchema = $schema['items'] ?? [];

        // If stored data is empty, return schema default or empty array
        if (empty($storedData)) {
            return $schema['default'] ?? [];
        }

        // If items are objects, merge each item with the item schema
        if (isset($itemSchema['type']) && $itemSchema['type'] === 'object') {
            return array_map(
                fn ($item) => $this->mergeObject($item, $itemSchema, $removeOrphans),
                $storedData
            );
        }

        // For primitive arrays, just return as-is
        return $storedData;
    }

    /**
     * Merge a single field based on its type
     *
     * @param  mixed  $storedValue  Stored value
     * @param  array  $fieldSchema  Field schema definition
     * @param  bool  $removeOrphans  Whether to remove fields not in schema
     * @return mixed Merged value
     */
    protected function mergeField($storedValue, array $fieldSchema, bool $removeOrphans = false)
    {
        // Handle null or missing values - use default from registry or schema
        if ($storedValue === null) {
            return $this->getDefaultValue($fieldSchema);
        }

        // Check for custom variable types - prioritize inputType over type
        // inputType is what developers use to specify custom variable types
        $inputType = $fieldSchema['inputType'] ?? null;
        $type = $fieldSchema['type'] ?? 'string';

        // First, check if inputType matches a custom variable type from registry
        if ($inputType) {
            $typeFromRegistry = $this->registry->get($inputType);
            if ($typeFromRegistry) {
                // For custom variable types, we trust the stored value as-is
                // The variable type will transform it when rendering
                return $storedValue;
            }
        }

        // Second, check if type matches a custom variable type from registry
        $typeFromRegistry = $this->registry->get($type);
        if ($typeFromRegistry) {
            // For custom variable types, we trust the stored value as-is
            // The variable type will transform it when rendering
            return $storedValue;
        }

        // Handle different field types
        return match ($type) {
            'object' => is_array($storedValue)
                ? $this->mergeObject($storedValue, $fieldSchema, $removeOrphans)
                : $this->getDefaultValue($fieldSchema),

            'array' => is_array($storedValue)
                ? $this->mergeArray($storedValue, $fieldSchema, $removeOrphans)
                : $this->getDefaultValue($fieldSchema),

            // For primitives, preserve stored value if type matches
            'string' => is_string($storedValue) ? $storedValue : $this->getDefaultValue($fieldSchema),
            'number', 'integer' => is_numeric($storedValue) ? $storedValue : $this->getDefaultValue($fieldSchema),
            'boolean' => is_bool($storedValue) ? $storedValue : $this->getDefaultValue($fieldSchema),

            default => $storedValue,
        };
    }

    /**
     * Extract default value from schema
     *
     * @param  array  $fieldSchema  Field schema definition
     * @return mixed Default value
     */
    protected function getDefaultValue(array $fieldSchema)
    {
        // Priority 1: Check for custom variable types in registry (by inputType)
        $inputType = $fieldSchema['inputType'] ?? null;
        if ($inputType) {
            $typeFromRegistry = $this->registry->get($inputType);
            if ($typeFromRegistry) {
                return null;

                return $typeFromRegistry->getDefaultValue();
            }
        }

        // Priority 2: Check for custom variable types in registry (by type)
        $type = $fieldSchema['type'] ?? 'string';
        $typeFromRegistry = $this->registry->get($type);
        if ($typeFromRegistry) {
            return null;

            return $typeFromRegistry->getDefaultValue();
        }

        // Priority 3: Use explicit default from schema if available
        if (array_key_exists('default', $fieldSchema)) {
            return $fieldSchema['default'];
        }

        // Priority 4: Handle anyOf (union types) - use first option's default
        if (isset($fieldSchema['anyOf']) && is_array($fieldSchema['anyOf'])) {
            $firstType = $fieldSchema['anyOf'][0] ?? [];
            if (array_key_exists('default', $firstType)) {
                return $firstType['default'];
            }
        }

        // Priority 5: Return type-appropriate default based on standard JSON Schema types
        return match ($type) {
            'string' => '',
            'number', 'integer' => 0,
            'boolean' => false,
            'array' => [],
            'object' => [],
            default => null,
        };
    }

    /**
     * Merge multiple blocks at once
     *
     * @param  array  $blocks  Array of blocks with their data and schemas
     * @param  bool  $removeOrphans  Whether to remove fields not in schema
     * @return array Array of merged results [blockId => mergedData]
     */
    public function mergeMany(array $blocks, bool $removeOrphans = false): array
    {
        $results = [];

        foreach ($blocks as $block) {
            $blockId = $block['id'] ?? null;
            $storedData = $block['data'] ?? [];
            $schema = $block['schema'] ?? [];

            if ($blockId && $schema) {
                $results[$blockId] = $this->merge($storedData, $schema, $removeOrphans);
            }
        }

        return $results;
    }
}

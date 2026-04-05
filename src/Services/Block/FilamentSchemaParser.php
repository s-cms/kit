<?php

namespace SmartCms\Kit\Services\Block;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group as ComponentsGroup;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Str;
use SmartCms\TemplateBuilder\Support\VariableTypeRegistry;

/**
 * FilamentSchemaParser
 *
 * Converts JSON Schema (from Zod schemas) into Filament form fields.
 * Supports OpenAPI 3.0 and JSON Schema Draft 2020-12 formats.
 *
 * @example
 * ```php
 * $parser = new FilamentSchemaParser();
 * $schema = json_decode(file_get_contents(storage_path('app/sections-schemas.json')), true);
 * $fields = $parser->parse($schema['Hero']['jsonSchema']);
 * ```
 */
class FilamentSchemaParser
{
    protected VariableTypeRegistry $registry;

    public function __construct(?VariableTypeRegistry $registry = null)
    {
        $this->registry = $registry ?? app(VariableTypeRegistry::class);
    }

    /**
     * Parse a JSON schema and return Filament form fields
     *
     * @param  array  $schema  The JSON schema to parse
     * @param  string  $parentPath  Optional parent path for nested fields
     * @param  bool  $inRepeater  Whether we're parsing fields inside a repeater
     * @param  string|null  $language  Optional language code for multilanguage support
     * @return array Array of Filament form components
     */
    public function parse(array $schema, string $parentPath = '', bool $inRepeater = false, ?string $language = null): array
    {
        // Resolve allOf and $ref before parsing
        $schema = $this->resolveReferences($schema);

        if (! isset($schema['properties']) && ! isset($schema['type'])) {
            return [];
        }

        // Handle array type at root level (like AdvantagesSection)
        if (isset($schema['type']) && $schema['type'] === 'array') {
            return [$this->parseArrayField('items', $schema, false, $parentPath, $language)];
        }

        // Handle object type with properties
        if (! isset($schema['properties'])) {
            return [];
        }

        $fields = [];
        $required = $schema['required'] ?? [];

        foreach ($schema['properties'] as $fieldName => $fieldSchema) {
            $field = $this->parseField($fieldName, $fieldSchema, in_array($fieldName, $required), $parentPath, $inRepeater, $language);

            if ($field !== null) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Resolve allOf and $ref references in schema
     *
     * @param  array  $schema  Schema to resolve
     * @param  array|null  $definitions  Available definitions for $ref resolution
     * @return array Resolved schema
     */
    protected function resolveReferences(array $schema, ?array $definitions = null): array
    {
        // Extract definitions from schema if available
        if (isset($schema['definitions'])) {
            $definitions = $schema['definitions'];
        }

        // Handle allOf - merge all schemas
        if (isset($schema['allOf'])) {
            $merged = $schema;
            unset($merged['allOf']);

            foreach ($schema['allOf'] as $subSchema) {
                $resolved = $this->resolveReferences($subSchema, $definitions);
                $merged = array_merge_recursive($merged, $resolved);
            }

            return $merged;
        }

        // Handle $ref - resolve reference
        if (isset($schema['$ref'])) {
            $ref = $schema['$ref'];

            // Handle internal references like "#/definitions/AdvantagesSection"
            if (str_starts_with($ref, '#/definitions/')) {
                $definitionName = str_replace('#/definitions/', '', $ref);

                if ($definitions && isset($definitions[$definitionName])) {
                    return $this->resolveReferences($definitions[$definitionName], $definitions);
                }
            }
        }

        return $schema;
    }

    /**
     * Parse a single field schema into a Filament component
     *
     * @param  string  $name  Field name
     * @param  array  $schema  Field schema
     * @param  bool  $required  Whether the field is required
     * @param  string  $parentPath  Optional parent path for nested fields
     * @param  bool  $inRepeater  Whether we're parsing fields inside a repeater
     * @param  string|null  $language  Optional language code for multilanguage support
     * @return mixed Filament form component or null
     */
    protected function parseField(string $name, array $schema, bool $required = false, string $parentPath = '', bool $inRepeater = false, ?string $language = null)
    {
        // Construct the full field path
        // The inRepeater flag only affects root-level fields (no 'data.' prefix)
        // Nested fields should always append to their parent path
        if ($parentPath === '') {
            // Root level: add language and 'data.' prefix
            if ($language) {
                $fullName = $inRepeater ? $name : "data.{$language}.{$name}";
            } else {
                $fullName = $inRepeater ? $name : 'data.' . $name;
            }
        } else {
            // Nested level: always append to parent path
            $fullName = $parentPath . '.' . $name;
        }

        // Handle anyOf (union types) - typically string | object
        if (isset($schema['anyOf'])) {
            return $this->parseUnionField($fullName, $schema, $required);
        }

        // Handle custom inputType metadata (including custom variable types)
        if (isset($schema['inputType'])) {
            $type = $this->registry->get($schema['inputType']);
            $typeSchema = $type->getSchema($fullName, $language) ?? null;
            if ($typeSchema) {
                return Fieldset::make(__('kit::admin.custom_type'))
                    ->label($schema['description'] ?? Str::title($name))
                    ->columns(1)
                    ->schema([$typeSchema]);
            }
        }

        // Try to parse using custom variable types from registry
        // Check if the type matches a registered variable type name
        $customField = $this->tryParseCustomVariableType($fullName, $schema, $required, $language);
        if ($customField !== null) {
            return $customField;
        }

        // Handle standard types
        $type = $schema['type'] ?? 'string';

        return match ($type) {
            'string' => $this->parseStringField($fullName, $schema, $required),
            'number', 'integer' => $this->parseNumberField($fullName, $schema, $required),
            'boolean' => $this->parseBooleanField($fullName, $schema, $required),
            'array' => $this->parseArrayField($fullName, $schema, $required),
            'object' => $this->parseObjectField($fullName, $schema, $required),
            default => null,
        };
    }

    /**
     * Parse union types (anyOf)
     * Creates a field that supports both simple string and complex object inputs
     */
    protected function parseUnionField(string $fullName, array $schema, bool $required): mixed
    {
        $inputType = $schema['inputType'] ?? null;

        // For image/link types, use the custom handler
        if ($inputType === 'image-upload') {
            return $this->parseImageField($fullName, $schema, $required);
        }

        if ($inputType === 'link-builder') {
            return $this->parseLinkField($fullName, $schema, $required);
        }

        // Default: use the first type (usually string) as the primary input
        $firstType = $schema['anyOf'][0] ?? null;

        if ($firstType && isset($firstType['type'])) {
            // For union fields, we already have the full name, so we need to handle it specially
            $type = $firstType['type'];

            return match ($type) {
                'string' => $this->parseStringField($fullName, $firstType, $required),
                'number', 'integer' => $this->parseNumberField($fullName, $firstType, $required),
                'boolean' => $this->parseBooleanField($fullName, $firstType, $required),
                'array' => $this->parseArrayField($fullName, $firstType, $required),
                'object' => $this->parseObjectField($fullName, $firstType, $required),
                default => null,
            };
        }

        return null;
    }

    /**
     * Parse custom inputType metadata
     */
    protected function parseCustomInputType(string $name, array $schema, bool $required, ?string $language = null): mixed
    {
        // Check if it's a registered custom variable type
        $customField = $this->tryParseCustomVariableType($name, $schema, $required, $language);
        if ($customField !== null) {
            return $customField;
        }

        return match ($schema['inputType']) {
            'image-upload' => $this->parseImageField($name, $schema, $required),
            'link-builder' => $this->parseLinkField($name, $schema, $required),
            'page-select' => $this->parsePageSelectField($name, $schema, $required),
            default => null,
        };
    }

    /**
     * Try to parse field using custom variable types from registry
     *
     * @param  string  $name  Field name
     * @param  array  $schema  Field schema
     * @param  bool  $required  Whether the field is required
     * @return mixed Filament component or null if no custom type found
     */
    protected function tryParseCustomVariableType(string $name, array $schema, bool $required, ?string $language = null): mixed
    {
        // Priority 1: Check if inputType matches a registered variable type
        $inputType = $schema['inputType'] ?? null;

        if ($inputType && $variableType = $this->registry->get($inputType)) {
            return $this->buildCustomVariableTypeComponent($name, $variableType, $required, $language);
        }

        // Priority 2: Check if the standard type field matches a registered variable type
        $type = $schema['type'] ?? null;

        if ($type && $variableType = $this->registry->get($type)) {
            return $this->buildCustomVariableTypeComponent($name, $variableType, $required, $language);
        }

        return null;
    }

    /**
     * Build a Filament component from a custom variable type
     *
     * @param  string  $name  Field name
     * @param  mixed  $variableType  Variable type instance
     * @param  bool  $required  Whether the field is required
     * @return mixed Filament component
     */
    protected function buildCustomVariableTypeComponent(string $name, mixed $variableType, bool $required, ?string $language = null): mixed
    {
        $component = $variableType->getSchema($name, $language);

        // Apply default value from the variable type
        $defaultValue = $variableType->getDefaultValue();
        if ($defaultValue !== null && method_exists($component, 'default')) {
            $component->default($defaultValue);
        }

        // Apply required validation if it's a Field component
        if ($required && method_exists($component, 'required')) {
            $component->required($required);
        }

        return $component;
    }

    /**
     * Parse string fields with enum support
     */
    protected function parseStringField(string $name, array $schema, bool $required): TextInput | Select | Textarea
    {
        // Handle enum (dropdown)
        if (isset($schema['enum'])) {
            return Select::make($name)
                ->label($schema['description'] ?? Str::title($name))
                ->options(array_combine($schema['enum'], $schema['enum']))
                ->default($schema['default'] ?? null)
                ->required($required)
                ->placeholder(__('kit::admin.select_an_option'));
        }

        // Handle URL format
        if (isset($schema['format']) && $schema['format'] === 'uri') {
            return TextInput::make($name)
                ->label($schema['description'] ?? Str::title($name))
                ->url()
                ->default($schema['default'] ?? null)
                ->required($required)
                ->placeholder('https://example.com');
        }

        // Regular text input
        return TextInput::make($name)
            ->label($schema['description'] ?? Str::title($name))
            ->default($schema['default'] ?? null)
            ->required($required)
            ->maxLength(255);
    }

    /**
     * Parse number fields
     */
    protected function parseNumberField(string $name, array $schema, bool $required): TextInput
    {
        return TextInput::make($name)
            ->label($schema['description'] ?? Str::title($name))
            ->numeric()
            ->default($schema['default'] ?? null)
            ->required($required)
            ->minValue($schema['minimum'] ?? null)
            ->maxValue($schema['maximum'] ?? null);
    }

    /**
     * Parse boolean fields
     */
    protected function parseBooleanField(string $name, array $schema, bool $required): Toggle
    {
        return Toggle::make($name)
            ->label($schema['description'] ?? Str::title($name))
            ->default($schema['default'] ?? false)
            ->required($required);
    }

    /**
     * Parse array fields
     */
    protected function parseArrayField(string $name, array $schema, bool $required, string $parentPath = '', ?string $language = null): Repeater
    {
        $itemSchema = $schema['items'] ?? [];

        // Build the repeater
        $repeater = Repeater::make($name)
            ->compact()
            ->label($schema['title'] ?? $schema['description'] ?? Str::title($name))
            ->required($required)
            ->addActionAlignment(Alignment::End)
            ->collapseAllAction(
                fn (Action $action) => $action->hidden(),
            )
            ->expandAllAction(
                fn (Action $action) => $action->hidden(),
            )
            ->collapsible();

        // Parse item schema - if it's an object, parse its properties
        // For array items, we pass inRepeater=true so fields don't get 'data.' prefix
        // Language is passed through for nested fields
        if (isset($itemSchema['type']) && $itemSchema['type'] === 'object') {
            $repeater->schema($this->parse($itemSchema, '', true, $language));
        } else {
            $repeater->schema($this->parse(['properties' => ['item' => $itemSchema], 'required' => []], '', true, $language));
        }

        // Add default values if available
        if (isset($schema['default'])) {
            $repeater->default($schema['default'])->mutateRelationshipDataBeforeFillUsing(function ($data) use ($schema) {
                return $schema['default'] ?? [];
            });
        }

        return $repeater;
    }

    /**
     * Parse object fields
     */
    protected function parseObjectField(string $name, array $schema, bool $required, ?string $language = null): Fieldset
    {
        // Pass the current field name as the parent path for nested fields
        // Nested fields will automatically append to this path
        $nestedFields = $this->parse($schema, $name, false, $language);

        return Fieldset::make($name)->schema($nestedFields)
            ->label($schema['description'] ?? Str::title($name));
        // ->required($required);
    }

    /**
     * Parse image upload field (custom inputType: image-upload)
     */
    protected function parseImageField(string $name, array $schema, bool $required): FileUpload
    {
        return FileUpload::make($name)
            ->label($schema['description'] ?? Str::title($name))
            ->image()
            ->imageEditor()
            ->imageEditorAspectRatios([
                '16:9',
                '4:3',
                '1:1',
            ])
            ->required($required)
            ->disk('public')
            ->directory('images')
            ->visibility('public')
            ->maxSize(5120) // 5MB
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->helperText($schema['description'] ?? __('kit::admin.upload_image'));
    }

    /**
     * Parse link builder field (custom inputType: link-builder)
     * Creates a grouped input for URL and target
     */
    protected function parseLinkField(string $name, array $schema, bool $required): ComponentsGroup
    {
        return ComponentsGroup::make([
            TextInput::make("{$name}.url")
                ->label(__('kit::admin.url'))
                ->url()
                ->required($required)
                ->columnSpan(2),

            Select::make("{$name}.target")
                ->label(__('kit::admin.target'))
                ->options([
                    '_self' => __('kit::admin.same_window'),
                    '_blank' => __('kit::admin.new_window'),
                    '_parent' => __('kit::admin.parent_frame'),
                    '_top' => __('kit::admin.top_frame'),
                ])
                ->default('_self')
                ->columnSpan(1),

            TextInput::make("{$name}.title")
                ->label(__('kit::admin.link_title'))
                ->placeholder(__('kit::admin.optional_hover_text'))
                ->columnSpan(1),
        ])
            // ->label($schema['description'] ?? Str::title($name))
            ->columns(4)
            ->columnSpanFull();
    }

    /**
     * Parse page select field (custom inputType: page-select)
     * Creates a select dropdown for Page models
     */
    protected function parsePageSelectField(string $name, array $schema, bool $required): Select
    {
        return Select::make($name)
            ->label($schema['description'] ?? Str::title($name))
            ->relationship('page', 'title') // Assumes a page relationship exists
            ->searchable()
            ->preload()
            ->required($required)
            ->placeholder(__('kit::admin.select_page'));
    }

    /**
     * Parse an entire section schema file
     *
     * @param  string  $filePath  Path to the sections-schemas.json file
     * @return array Associative array [sectionName => fields]
     */
    public static function parseFile(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new \RuntimeException("Schema file not found: {$filePath}");
        }

        $schemas = json_decode(file_get_contents($filePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in schema file: ' . json_last_error_msg());
        }

        $parser = new self;
        $result = [];

        foreach ($schemas['schemas'] as $sectionName => $sectionData) {
            $result[$sectionName] = [
                'name' => $sectionData['title'],
                'description' => $sectionData['description'],
                'fields' => $parser->parse($sectionData['jsonSchema']),
            ];
        }

        return $result;
    }

    /**
     * Get fields for a specific section
     *
     * @param  array  $field  The field schema array
     * @param  string|null  $language  Optional language code for multilanguage support
     * @return array Filament form fields
     */
    public static function getFieldsForSection(array $field, ?string $language = null): array
    {
        $parser = new self;

        return $parser->parse($field, '', false, $language);
    }
}

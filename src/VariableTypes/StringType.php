<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class StringType implements VariableTypeInterface
{
    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'string';
    }

    public function getDefaultValue(): mixed
    {
        return 'Default text';
    }

    public function getSchema(string $name, ?string $language = null): Field | Component
    {
        return TextInput::make($name);
    }

    public function getValue(mixed $value): mixed
    {
        return $value ?? $this->getDefaultValue();
    }
}

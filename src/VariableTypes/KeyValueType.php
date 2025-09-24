<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\Component;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class KeyValueType implements VariableTypeInterface
{
    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'key_value';
    }

    public function getDefaultValue(): mixed
    {
        return [
            'key' => 'value',
        ];
    }

    public function getSchema(string $name): Field | Component
    {
        return KeyValue::make($name);
    }

    public function getValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $this->getDefaultValue();
        }

        return $value;
    }
}

<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class PhoneType implements VariableTypeInterface
{
    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'phone';
    }

    public function getDefaultValue(): mixed
    {
        return '+111 111 111111';
    }

    public function getSchema(string $name, ?string $language = null): Field | Component
    {
        return Select::make($name)->options(collect(app('s')->get('company_info.phones', []))->pluck('value'));
    }

    public function getValue(mixed $value): mixed
    {
        foreach (app('s')->get('company_info.phones', []) as $key => $item) {
            if ($key == $value) {
                return $item['value'] ?? $this->getDefaultValue();
            }
        }

        return $this->getDefaultValue();
    }
}

<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class AddressType implements VariableTypeInterface
{
    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'address';
    }

    public function getDefaultValue(): mixed
    {
        return '123 Main St, Anytown, USA';
    }

    public function getSchema(string $name, ?string $language = null): Field | Component
    {
        return Select::make($name)->options(collect(app('s')->get('company_info.addresses', []))->pluck('value'));
    }

    public function getValue(mixed $value): mixed
    {
        return collect(app('s')->get('company_info.addresses', []))->only($value)->first()['value'] ?? $this->getDefaultValue();
    }
}

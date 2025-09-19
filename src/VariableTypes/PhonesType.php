<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class PhonesType implements VariableTypeInterface
{
    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'phones';
    }

    public function getDefaultValue(): mixed
    {
        return ['+11111111111'];
    }

    public function getSchema(string $name): Field | Component
    {
        return Select::make($name)->options(collect(app('s')->get('company_info.phones', []))->pluck('value'))->multiple();
    }

    public function getValue(mixed $value): mixed
    {
        return collect(app('s')->get('company_info.phones', []))->mapWithKeys(fn($item, $key): array => [
            'id' => $key,
            'value' => $item['value'],
        ])->whereIn('id', $value)->pluck('value')->toArray();
    }
}

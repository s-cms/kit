<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class EmailsType implements VariableTypeInterface
{
    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'emails';
    }

    public function getDefaultValue(): mixed
    {
        return ['example@example.com'];
    }

    public function getSchema(string $name): Field | Component
    {
        return Select::make($name)->options(collect(app('s')->get('company_info.emails', []))->pluck('value'))->multiple();
    }

    public function getValue(mixed $value): mixed
    {
        return collect(app('s')->get('company_info.emails', []))->mapWithKeys(fn($item, $key): array => [
            'id' => $key,
            'value' => $item['value'],
        ])->whereIn('id', $value)->pluck('value')->toArray();
    }
}

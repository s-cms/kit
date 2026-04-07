<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use SmartCms\Menu\Models\Menu;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class MenuType implements VariableTypeInterface
{
    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'menu';
    }

    public function getDefaultValue(): mixed
    {
        return [];
    }

    public function getSchema(string $name, ?string $language = null): Field | Component
    {
        return Select::make($name)->options(Menu::query()->pluck('name', 'id'))->required();
    }

    public function getValue(mixed $value): mixed
    {
        return Menu::find($value)->links ?? $this->getDefaultValue();
    }
}

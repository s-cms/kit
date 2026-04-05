<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use SmartCms\Forms\Models\Form;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class FormType implements VariableTypeInterface
{
    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'form';
    }

    public function getDefaultValue(): mixed
    {
        return new Form;
    }

    public function getSchema(string $name, ?string $language = null): Field | Component
    {
        return Select::make($name)->label(__('kit::admin.form'))->options(Form::query()->pluck('name', 'id'));
    }

    public function getValue(mixed $value): mixed
    {
        return Form::find($value) ?? $this->getDefaultValue();
    }
}

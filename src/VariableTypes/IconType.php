<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class IconType implements VariableTypeInterface
{
    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'icon';
    }

    public function getDefaultValue(): mixed
    {
        return svg('heroicon-o-' . Heroicon::BugAnt->value);
    }

    public function getSchema(string $name): Field | Component
    {
        $options = collect(Heroicon::cases())->mapWithKeys(function (Heroicon $icon): array {
            $iconHtml = \Filament\Support\generate_icon_html($icon)->toHtml();

            return [$icon->value => "<div style='display: flex; gap: 10px; align-items: center;'> $iconHtml <span class='text-sm'>{$icon->name}</span></div>"];
        });

        return Select::make($name)->options($options)->allowHtml()->searchable()->hint(fn () => str()->of("You can use any icon from <a href='https://heroicons.com' target='_blank'>Heroicons</a> set")->toHtmlString());
    }

    public function getValue(mixed $value): mixed
    {
        if (! $value || ! is_string($value)) {
            return $this->getDefaultValue();
        }
        if (str_starts_with($value, 'o-')) {
            return svg("heroicon-{$value}");
        }

        return svg('heroicon-m-' . $value);
    }
}

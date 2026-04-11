<?php

namespace SmartCms\Kit\VariableTypes;

use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
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
        return null;
    }

    public function getSchema(string $name, ?string $language = null): Field | Component
    {
        $options = collect(LucideIcon::cases())->mapWithKeys(function (LucideIcon $icon): array {
            $iconHtml = \Filament\Support\generate_icon_html($icon)->toHtml();

            return [$icon->value => "<div style='display: flex; gap: 10px; align-items: center;'> $iconHtml <span class='text-sm'>{$icon->name}</span></div>"];
        });

        return Select::make($name)->options($options)->allowHtml()->searchable()->hint(fn () => str()->of(__('kit::admin.lucide_icon_hint'))->toHtmlString());
    }

    public function getValue(mixed $value): mixed
    {
        if (! $value || ! is_string($value)) {
            return null;
        }

        return $value;
    }
}

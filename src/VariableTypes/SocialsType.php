<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Fluent;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class SocialsType implements VariableTypeInterface
{
    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'socials';
    }

    public function getDefaultValue(): mixed
    {
        return collect([[
            'name' => 'Facebook',
            'url' => 'https://www.facebook.com',
            'image' => 'https://www.facebook.com/favicon.ico',
        ]])->map(fn ($item): \Illuminate\Support\Fluent => new Fluent([
            'name' => $item['name'],
            'url' => [
                'title' => $item['name'],
                'type' => 'link',
                'is_external' => true,
                'url' => $item['url'],
            ],
            'image' => validateImage($item['image']),
        ]));
    }

    public function getSchema(string $name): Field | Component
    {
        return Select::make($name)->options(collect(app('s')->get('branding.socials', []))->pluck('name'))->multiple()->native(false)->searchable();
    }

    public function getValue(mixed $value): mixed
    {

        return collect(app('s')->get('branding.socials', []))->only($value)->map(fn ($item): array => [
            'name' => $item['name'],
            'url' => [
                'title' => $item['name'],
                'type' => 'link',
                'is_external' => true,
                'url' => $item['link'],
            ],
            'image' => validateImage($item['image']),
        ]);
    }
}

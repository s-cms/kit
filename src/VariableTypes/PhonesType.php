<?php

namespace SmartCms\Kit\VariableTypes;

use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
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
        return [
            [
                'title' => '+11 111 111 11 11',
                'type' => 'tel',
                'is_external' => false,
                'icon' => LucideIcon::Image->value,
                'url' => 'tel:+11111111111',
            ],
        ];
    }

    public function getSchema(string $name): Field | Component
    {
        return Select::make($name)->options(collect(app('s')->get('company_info.phones', []))->pluck('value'))->multiple();
    }

    public function getValue(mixed $value): mixed
    {
        return collect(app('s')->get('company_info.phones', []))->only($value)->map(fn ($item): array => [
            'title' => $item['value'],
            'type' => 'tel',
            'is_external' => false,
            'icon' => $item['icon'] ?? LucideIcon::Image->value,
            'url' => $this->formatPhone($item['value'] ?? ''),
        ])->toArray();
    }

    private function formatPhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9+]/', '', $phone);
        $clean = ltrim($clean, '+');

        return 'tel:' . $clean;
    }
}

<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class HeadingType implements VariableTypeInterface
{
    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'heading';
    }

    public function getDefaultValue(): mixed
    {
        return [
            'tag' => 'h2',
            'title' => 'Default heading title',
        ];
    }

    public function getSchema(string $name): Field | Component
    {
        return Grid::make()->schema([
            Grid::make(2)->columnSpanFull()->schema([
                Select::make($name . '.heading_type')
                    ->label(__('kit::admin.heading_type'))
                    ->options([
                        'h1' => 'H1',
                        'h2' => 'H2',
                        'h3' => 'H3',
                        'none' => 'None',
                    ])
                    ->required()
                    ->default('h2')->formatStateUsing(fn ($state) => $state ?? 'h2'),
                TextInput::make($name . '.title')->label(__('kit::admin.title'))->required(),
            ]),
            TextInput::make($name . '.heading')->label(__('kit::admin.heading'))->required()->hidden(fn ($get): bool => $get($name . '.scope') != 'custom')->columnSpanFull(),
        ])->columns(2);
    }

    public function getValue(mixed $value): mixed
    {
        return [
            'tag' => $value['heading_type'] ?? 'h2',
            'title' => $value['title'] ?? 'Default title',
        ];
    }
}

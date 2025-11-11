<?php

namespace SmartCms\Kit\Admin\Resources\BlockTemplates\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use SmartCms\Kit\Models\Block;
use SmartCms\Support\Admin\Components\Layout\LeftGrid;
use SmartCms\Support\Admin\Components\Layout\RightGrid;

class BlockTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()->gridContainer()
                    ->columns([
                        '@md' => 3,
                        '@xl' => 4,
                    ])
                    ->columnSpanFull()
                    ->schema([
                        LeftGrid::make()->schema([
                            Section::make(__('kit::admin.template_blocks'))
                                ->description(__('kit::admin.template_blocks_description'))
                                ->schema([
                                    Repeater::make('blocks')
                                        ->relationship()
                                        ->label(__('kit::admin.blocks'))
                                        ->reorderable('sorting')
                                        ->defaultItems(0)
                                        ->addActionLabel(__('kit::admin.add_block'))
                                        ->schema([
                                            Select::make('id')
                                                ->label(__('kit::admin.block'))
                                                ->options(Block::query()->where('status', true)->pluck('title', 'id'))
                                                ->required()
                                                ->searchable()
                                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                ->columnSpanFull(),
                                            Grid::make(2)->schema([
                                                Toggle::make('status')
                                                    ->label(__('kit::admin.active'))
                                                    ->default(true),
                                                TextInput::make('sorting')
                                                    ->label(__('kit::admin.sorting'))
                                                    ->numeric()
                                                    ->default(fn ($get, $livewire) => $livewire->mountedTableActionRecord ? count($livewire->mountedTableActionRecord->blocks) + 1 : 1),
                                            ]),
                                            Grid::make(2)->schema([
                                                DateTimePicker::make('show_from')
                                                    ->label(__('kit::admin.show_from'))
                                                    ->nullable(),
                                                DateTimePicker::make('show_until')
                                                    ->label(__('kit::admin.show_until'))
                                                    ->nullable(),
                                            ]),
                                        ])
                                        ->columns(2)
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => Block::find($state['id'])?->title),
                                ]),
                        ]),
                        RightGrid::make()->schema([
                            Section::make(__('kit::admin.template_information'))
                                ->schema([
                                    TextInput::make('name')
                                        ->label(__('kit::admin.template_name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('type')
                                        ->label(__('kit::admin.page_type'))
                                        ->helperText(__('kit::admin.template_type_helper'))
                                        ->placeholder(__('kit::admin.template_type_placeholder'))
                                        ->maxLength(255),
                                ]),
                        ]),
                    ]),
            ])->columns(1);
    }
}

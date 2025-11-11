<?php

namespace SmartCms\Kit\Admin\Resources\Pages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Models\Page as ModelsPage;
use SmartCms\Kit\Support\Contracts\PageStatus;
use SmartCms\Support\Admin\Components\Forms\ImageUpload;

class PageSummary extends Page
{
    public static function make(): array
    {
        $imagePath = '';

        return [
            Section::make('Status')->icon(function (Get $get): \Filament\Support\Icons\Heroicon {
                $status = $get('status');

                return match ($status) {
                    'draft' => Heroicon::OutlinedSun,
                    'scheduled' => Heroicon::OutlinedCalendarDays,
                    default => Heroicon::Sun,
                };
            })->compact()
                ->schema([
                    Radio::make('status')->hiddenLabel()
                        ->disabled(fn ($record): bool => $record->id == 1)
                        ->options(PageStatus::class)->default('active')->reactive(),
                    DateTimePicker::make('published_at')->reactive()->seconds(false)->default(now())->hidden(fn ($get): bool => $get('status')?->value != 'scheduled'),
                ]),
            Section::make()->compact()->schema([
                ImageUpload::make('image', $imagePath, __('kit::admin.image')),
                ImageUpload::make('banner', $imagePath, __('kit::admin.banner')),
            ])->columns(1),
            Section::make()->compact()->schema([
                Select::make('layout_id')
                    ->options(fn (ModelsPage $record) => $record->getAvailableLayouts())
                    ->label(__('kit::admin.layout')),
            ])->columns(1),
            Section::make(__('kit::admin.indexation'))->icon(function (Get $get): \Filament\Support\Icons\Heroicon {
                $index = $get('is_index') ?? true;

                return match ($index) {
                    true => Heroicon::OutlinedMagnifyingGlass,
                    default => Heroicon::OutlinedMagnifyingGlassMinus
                };
            })->compact()->schema([
                Toggle::make('is_index')->label(__('kit::admin.is_index'))->hiddenLabel()->default(true)->reactive(),
            ]),
            Section::make(__('kit::admin.child_default_blocks'))
                ->icon(Heroicon::Squares2x2)
                ->compact()
                ->visible(fn (Get $get, ?ModelsPage $record) => $record?->canHaveChildren() ?? in_array($get('type'), ['category']))
                ->schema([
                    Select::make('settings.child_blocks_page')
                        ->label(__('kit::admin.default_blocks_for_child_pages'))
                        ->helperText(__('kit::admin.default_blocks_for_child_pages_helper'))
                        ->options(Block::query()->where('status', true)->pluck('type', 'id'))
                        ->multiple()
                        ->searchable(),
                    Select::make('settings.child_blocks_category')
                        ->label(__('kit::admin.default_blocks_for_child_categories'))
                        ->helperText(__('kit::admin.default_blocks_for_child_categories_helper'))
                        ->options(Block::query()->where('status', true)->pluck('type', 'id'))
                        ->multiple()
                        ->searchable(),
                ]),
        ];
    }
}

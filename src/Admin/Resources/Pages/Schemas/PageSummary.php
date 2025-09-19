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
use Illuminate\Database\Eloquent\Model;
use SmartCms\Kit\Support\Contracts\PageStatus;
use SmartCms\Support\Admin\Components\Forms\ImageUpload;
use SmartCms\TemplateBuilder\Models\Layout;

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
                        ->disabled(fn($record): bool => $record->id == 1)
                        ->options(PageStatus::class)->default('active')->reactive(),
                    DateTimePicker::make('published_at')->reactive()->seconds(false)->default(now())->hidden(fn ($get): bool => $get('status')?->value != 'scheduled'),
                ]),
            Section::make()->compact()->schema([
                ImageUpload::make('image', $imagePath, __('kit::admin.image')),
                ImageUpload::make('banner', $imagePath, __('kit::admin.banner')),
            ])->columns(1),
            Section::make()->compact()->schema([
                Select::make('layout_id')
                    ->options(fn(Model $record) => Layout::query()
                        ->when($record->is_root, fn($query) => $query->where('path', 'like', '%divisions%'))
                        ->when(! $record->is_root, fn($query) => $query->where('path', 'like', '%pages%'))
                        ->pluck('name', 'id'))
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
        ];
    }
}

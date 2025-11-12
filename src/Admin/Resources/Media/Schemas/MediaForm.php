<?php

namespace SmartCms\Kit\Admin\Resources\Media\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use SmartCms\ModelTranslate\Admin\Components\Forms\Translated;
use SmartCms\Support\Admin\Components\Layout\FormGrid;
use SmartCms\Support\Admin\Components\Layout\LeftGrid;
use SmartCms\Support\Admin\Components\Layout\RightGrid;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormGrid::make()->schema([
                    LeftGrid::make()->schema([
                        Section::make(__('kit::admin.media_information'))->schema([
                            ViewField::make('preview')
                                ->view('kit::admin.media.preview')
                                ->columnSpanFull(),

                            Grid::make(2)->schema([
                                Placeholder::make('file_name')
                                    ->label(__('kit::admin.file_name'))
                                    ->content(fn ($record) => $record?->file_name ?? '-'),

                                Placeholder::make('mime_type')
                                    ->label(__('kit::admin.mime_type'))
                                    ->content(fn ($record) => $record?->mime_type ?? '-'),

                                Placeholder::make('size')
                                    ->label(__('kit::admin.file_size'))
                                    ->content(fn ($record) => $record ? format_bytes($record->size) : '-'),

                                Placeholder::make('dimensions')
                                    ->label(__('kit::admin.dimensions'))
                                    ->content(
                                        fn ($record) => $record
                                        ? ($record->getCustomProperty('width', 0) . ' × ' . $record->getCustomProperty('height', 0))
                                        : '-'
                                    ),

                                Placeholder::make('created_at')
                                    ->label(__('kit::admin.uploaded_at'))
                                    ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? '-'),
                            ]),
                        ]),

                        Section::make(__('kit::admin.alt_text'))->schema([
                            Translated::make('alt')
                                ->label(__('kit::admin.alt_text'))
                                ->helperText(__('kit::admin.alt_text_help'))
                                ->getStateUsing(function ($record) {
                                    if (! $record) {
                                        return [];
                                    }
                                    $alt = $record->getCustomProperty('alt', []);

                                    return is_array($alt) ? $alt : [];
                                })
                                ->afterStateUpdated(function ($state, $record) {
                                    if ($record) {
                                        $record->setCustomProperty('alt', $state ?? []);
                                        $record->save();
                                    }
                                })
                                ->dehydrated(false),
                        ]),
                    ]),

                    RightGrid::make()->schema([
                        Section::make(__('kit::admin.conversions'))->schema([
                            ViewField::make('conversions')
                                ->view('kit::admin.media.conversions')
                                ->columnSpanFull(),
                        ]),
                    ]),
                ]),
            ])->columns(1);
    }
}

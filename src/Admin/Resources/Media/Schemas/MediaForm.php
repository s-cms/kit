<?php

namespace SmartCms\Kit\Admin\Resources\Media\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('kit::admin.media_information'))->schema([
                    ViewField::make('preview')
                        ->view('kit::admin.media.preview')
                        ->columnSpanFull(),

                    TextInput::make('name')
                        ->label(__('kit::admin.name'))
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Auto-generate slug from name
                            if ($state) {
                                $set('name', Str::slug($state));
                            }
                        })
                        ->helperText(__('kit::admin.media_name_help')),
                ]),
            ])->columns(1);
    }
}

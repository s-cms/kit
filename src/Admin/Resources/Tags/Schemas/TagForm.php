<?php

namespace SmartCms\Kit\Admin\Resources\Tags\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use SmartCms\Support\Admin\Components\Forms\NameField;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        NameField::make('name')
                            ->label(__('kit::admin.tag_name'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $state, string $operation, Set $set): void {
                                if ($operation === 'edit') {
                                    return;
                                }
                                $slug = Str::slug($state);
                                $set('slug.' . main_lang(), $slug);
                            }),
                        TextInput::make('slug.' . main_lang())
                            ->label(__('kit::admin.slug'))
                            ->required()
                            ->helperText(__('kit::admin.tag_slug_helper')),
                        TextInput::make('type')
                            ->label(__('kit::admin.tag_type'))
                            ->helperText(__('kit::admin.tag_type_helper'))
                            ->placeholder('general'),
                    ]),
            ])->columns(1);
    }
}

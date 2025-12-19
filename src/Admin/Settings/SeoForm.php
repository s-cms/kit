<?php

namespace SmartCms\Kit\Admin\Settings;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;
use SmartCms\Kit\Forms\Components\MediaPicker;

class SeoForm
{
    public static function make(): Tab
    {
        return Tab::make(__('kit::admin.seo'))->schema([
            Flex::make([
                TextInput::make('gtm')->label(__('kit::admin.google_tag'))->string(),
                Toggle::make('indexation')->inline(false)->label(__('kit::admin.indexation'))->required(),
            ]),
            Grid::make(2)->schema([
                Select::make('og_type')
                    ->options([
                        'website' => __('kit::admin.website'),
                        'company' => __('kit::admin.company'),
                        'article' => __('kit::admin.article'),
                        'video' => __('kit::admin.video'),
                        'product' => __('kit::admin.product'),
                    ])
                    ->label(__('kit::admin.og_type')),
                MediaPicker::make('og_image')->label(__('kit::admin.og_image')),
            ]),
            Fieldset::make(__('kit::admin.title'))->schema([
                TextInput::make('title.prefix')
                    ->label(__('kit::admin.prefix'))
                    ->string()
                    ->helperText(__('kit::admin.title_prefix')),
                TextInput::make('title.suffix')
                    ->label(__('kit::admin.suffix'))
                    ->helperText(__('kit::admin.title_suffix'))
                    ->string(),
            ]),
            Fieldset::make(__('kit::admin.description'))->schema([
                TextInput::make('description.prefix')
                    ->label(__('kit::admin.prefix'))
                    ->string()
                    ->helperText(__('kit::admin.description_prefix')),
                TextInput::make('description.suffix')
                    ->label(__('kit::admin.suffix'))
                    ->helperText(__('kit::admin.description_suffix'))
                    ->string(),
            ]),
            Repeater::make('custom_meta')
                ->label(__('kit::admin.custom_meta'))
                ->schema([
                    TextInput::make('name')->label(__('kit::admin.name'))->string(),
                    TextInput::make('description')->label(__('kit::admin.description'))->string(),
                    Textarea::make('meta_tags')->label(__('kit::admin.meta_tags')),
                ])
                ->default([]),
            Repeater::make('custom_scripts')
                ->label(__('kit::admin.custom_scripts'))
                ->schema([
                    TextInput::make('name')->label(__('kit::admin.name'))->string(),
                    TextInput::make('description')->label(__('kit::admin.description'))->string(),
                    Textarea::make('scripts')->label(__('kit::admin.scripts')),
                ])
                ->default([]),
        ]);
    }
}

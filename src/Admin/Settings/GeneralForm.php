<?php

namespace SmartCms\Kit\Admin\Settings;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use SmartCms\Kit\Forms\Components\MediaPicker;
use SmartCms\Lang\Models\Language;

class GeneralForm
{
    public static function make(): Tab
    {
        return Tab::make(__('kit::admin.general'))->schema([
            TextInput::make('company_name')
                ->label(__('kit::admin.company_name'))
                ->required(),
            Select::make('main_language')
                ->label(__('kit::admin.main_language'))
                ->live()
                ->afterStateUpdated(function (
                    mixed $state,
                    Set $set,
                    Get $get,
                ): void {
                    $additionalLanguages = $get('additional_languages') ?? [];
                    $frontLanguages = $get('front_languages') ?? [];
                    $set(
                        'additional_languages',
                        array_filter(
                            $additionalLanguages,
                            fn ($language): bool => $language !== $state,
                        ),
                    );
                    $set(
                        'front_languages',
                        array_filter(
                            $frontLanguages,
                            fn ($language): bool => $language !== $state,
                        ),
                    );
                })
                ->options(Language::query()->pluck('name', 'id')->toArray())
                ->required(),
            Toggle::make('is_multi_lang')
                ->label(__('kit::admin.is_multi_lang'))
                ->required()
                ->live(),
            Select::make('additional_languages')
                ->label(__('kit::admin.additional_languages'))
                ->options(function (Get $get) {
                    $mainLanguage = $get('main_language');

                    return Language::query()
                        ->where('id', '!=', $mainLanguage)
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->multiple()
                ->live()
                ->required()
                ->hidden(fn ($get): bool => ! $get('is_multi_lang')),
            Select::make('front_languages')
                ->label(__('kit::admin.front_languages'))
                ->options(function ($get) {
                    $mainLanguage = $get('main_language');

                    return Language::query()
                        ->whereIn('id', $get('additional_languages') ?? [])
                        ->where('id', '!=', $mainLanguage)
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->live()
                ->multiple()
                ->required()
                ->hidden(fn ($get): bool => ! $get('is_multi_lang')),
            Flex::make([
                MediaPicker::make('branding.logo')->label(
                    __('kit::admin.logo'),
                ),
                MediaPicker::make('branding.favicon')->label(
                    __('kit::admin.favicon'),
                ),
                MediaPicker::make('branding.apple_touch_icon')->label(
                    __('kit::admin.apple_touch_icon'),
                ),
                MediaPicker::make('branding.no_image')->label(
                    __('kit::admin.no_image'),
                ),
            ])->columns(2),
        ]);
    }
}

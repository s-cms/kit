<?php

namespace SmartCms\Kit\Admin\Settings;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;

class CompanyInfoForm
{
    public static function make(): Tab
    {
        $addressSchema = [];

        // foreach (get_active_languages() as $language) {
        //     $addressSchema[] = Hidden::make($language->slug)
        //         ->label($language->name);
        // }
        return Tab::make(__('kit::admin.company_info'))
            ->schema([
                Repeater::make('company_info.phones')->label(__('kit::admin.phones'))
                    ->schema([
                        TextInput::make('value')
                            ->rules('required', 'regex:/^\+[0-9]{1,3} [0-9]{3} [0-9]{6}$/')
                            ->placeholder('+999 99 999 99 99')
                            ->mask('+999 99 999 99 99'),
                    ]),
                Repeater::make('company_info.emails')->label(__('kit::admin.emails'))
                    ->schema([
                        TextInput::make('value')
                            ->email()
                            ->label(__('kit::admin.email'))
                            ->required(),
                    ]),
                Repeater::make('company_info.addresses')->label(__('kit::admin.addresses'))
                    ->schema([
                        ...$addressSchema,
                        TextInput::make('value')
                            ->label(__('kit::admin.branch_name'))
                            ->required()->suffixAction(Action::make('translate')
                            ->icon('heroicon-o-language')
                            ->hidden(fn ($get): bool => ! $get('is_multi_lang'))
                            ->fillForm(function ($get): array {
                                $values = [];
                                foreach (get_active_languages() as $language) {
                                    $values[$language->slug] = $get($language->slug);
                                }

                                return $values;
                            })
                            ->schema(function ($form) {
                                $schema = [];
                                foreach (get_active_languages() as $language) {
                                    $schema[] = TextInput::make($language->slug)
                                        ->label($language->name);
                                }

                                return $form->schema($schema);
                            })->action(function ($data, $set): void {
                                foreach ($data as $key => $value) {
                                    $set($key, $value);
                                }
                            })),
                    ]),
            ]);
    }
}

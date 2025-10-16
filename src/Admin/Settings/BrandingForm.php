<?php

namespace SmartCms\Kit\Admin\Settings;

use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use SmartCms\Kit\Admin\Components\IconInput;
use SmartCms\Support\Admin\Components\Forms\ImageUpload;

class BrandingForm
{
    public static function make(): Tab
    {
        $icons = collect(LucideIcon::cases())->mapWithKeys(function (LucideIcon $icon): array {
            $iconHtml = \Filament\Support\generate_icon_html($icon)->toHtml();

            return [$icon->value => "<div style='display: flex; gap: 10px; align-items: center;'> $iconHtml <span class='text-sm'>{$icon->name}</span></div>"];
        });

        return Tab::make(__('kit::admin.branding'))
            ->schema([
                Repeater::make('branding.socials')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('support::admin.name'))
                            ->string()
                            ->required(),
                        TextInput::make('link')
                            ->label(__('kit::admin.url'))
                            ->string()
                            ->required(),
                        Select::make('icon')->options($icons)->allowHtml()->searchable()->hint(fn () => str()->of("You can use any icon from <a href='https://lucide.dev' target='_blank'>Lucide</a> set")->toHtmlString()),
                        ImageUpload::make('image', 'branding', __('kit::admin.icon')),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? __('kit::admin.social'))
                    ->cloneable()
                    ->compact()
                    ->addActionAlignment(Alignment::End)
                    ->collapseAllAction(
                        fn (Action $action) => $action->hidden(),
                    )
                    ->expandAllAction(
                        fn (Action $action) => $action->hidden(),
                    )
                    ->default([]),
                Repeater::make('company_info.phones')->label(__('kit::admin.phones'))
                    ->schema([
                        FusedGroup::make([
                            IconInput::make('icon')->placeholder(__('kit::admin.icon')),
                            TextInput::make('value')->columnSpan(2)
                                ->rules('required', 'regex:/^\+[0-9]{1,3} [0-9]{3} [0-9]{6}$/')
                                ->placeholder('+999 99 999 99 99')
                                ->mask('+999 99 999 99 99'),
                        ])->columns(3),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['value'] ?? __('kit::admin.phone'))
                    ->collapsible()
                    ->collapsed()
                    ->cloneable()
                    ->compact()
                    ->collapseAllAction(
                        fn (Action $action) => $action->hidden(),
                    )
                    ->expandAllAction(
                        fn (Action $action) => $action->hidden(),
                    )
                    ->addActionAlignment(Alignment::End),
                Repeater::make('company_info.emails')->label(__('kit::admin.emails'))
                    ->schema([
                        FusedGroup::make([
                            IconInput::make('icon')->placeholder(__('kit::admin.icon')),
                            TextInput::make('value')->columnSpan(2)
                                ->email()
                                ->placeholder('example@example.com')
                                ->label(__('kit::admin.email'))
                                ->required()->belowContent([
                                    Icon::make(Heroicon::InformationCircle),
                                    'This is the user\'s full name.',
                                    Action::make('generate'),
                                ]),
                        ])->columns(3),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['value'] ?? __('kit::admin.email'))
                    ->collapsible()
                    ->collapsed()
                    ->cloneable()
                    ->compact()
                    ->collapseAllAction(
                        fn (Action $action) => $action->hidden(),
                    )
                    ->expandAllAction(
                        fn (Action $action) => $action->hidden(),
                    )
                    ->addActionAlignment(Alignment::End),
            ]);
    }
}

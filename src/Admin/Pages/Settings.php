<?php

namespace SmartCms\Kit\Admin\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use SmartCms\Kit\Admin\Enums\NavigationGroup;
use SmartCms\Kit\Admin\Settings\BrandingForm;
use SmartCms\Kit\Admin\Settings\CompanyInfoForm;
use SmartCms\Kit\Admin\Settings\GeneralForm;
use SmartCms\Kit\Admin\Settings\NotificationForm;
use SmartCms\Kit\Admin\Settings\SeoForm;
use SmartCms\Kit\Admin\Settings\SystemForm;
use SmartCms\Kit\Admin\Settings\ThemeForm;
use SmartCms\Lang\Models\Language;
use SmartCms\PanelSettings\SettingsPage;
use SmartCms\Support\Admin\Components\Actions\HelpAction;
use UnitEnum;

/**
 * @property mixed $form
 */
class Settings extends SettingsPage
{
    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.settings');
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return NavigationGroup::System;
    }

    public function getBreadcrumbs(): array
    {
        return [
            Dashboard::getUrl() => Dashboard::getNavigationLabel(),
            self::getUrl() => self::getNavigationLabel(),
        ];
    }

    protected static string | BackedEnum | null $navigationIcon = Heroicon::Cog6Tooth;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->fillForm();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Settings')
                    ->persistTabInQueryString()
                    ->id('settings-tabs')
                    ->schema([
                        GeneralForm::make(),
                        BrandingForm::make(),
                        // CompanyInfoForm::make(),
                        SeoForm::make(),
                        // ImagesForm::make(),
                        NotificationForm::make(),
                        // ThemeForm::make(),
                        SystemForm::make(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            // HelpAction::make(__('kit::admin.help_settings')),
            // Action::make('save_2')
            //     ->label(__('kit::admin.save'))
            //     ->icon('heroicon-o-check-circle')
            //     ->action(function () {
            //         $this->save();
            //     }),
            // Action::make('cancel')
            //     ->color('gray')
            //     ->label(__('kit::admin.cancel'))
            //     ->url(fn () => self::getUrl()),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        if (! $data['is_multi_lang']) {
            $data['additional_languages'] = [];
            $data['front_languages'] = [];
        }
        $this->form->fill($data);
        parent::save();
        Language::query()->where('id', $data['main_language'])->update([
            'is_default' => true,
            'is_admin_active' => true,
            'is_frontend_active' => true,
        ]);
        if ($data['main_language'] != main_lang_id()) {
            Language::query()->where('id', main_lang_id())->update([
                'is_default' => false,
                'is_admin_active' => false,
                'is_frontend_active' => false,
            ]);
        }
        Language::query()->where('id', '!=', $data['main_language'])->update([
            'is_default' => false,
            'is_admin_active' => false,
            'is_frontend_active' => false,
        ]);
        Language::query()->where('is_default', false)->whereIn('id', $data['additional_languages'] ?? [])->update([
            'is_admin_active' => true,
            'is_frontend_active' => false,
        ]);
        Language::query()->where('is_default', false)->whereIn('id', $data['front_languages'] ?? [])->update([
            'is_admin_active' => true,
            'is_frontend_active' => true,
        ]);
        $favicon = $this->form->getState()['branding']['favicon'] ?? null;
        if ($favicon) {
            File::copy(Storage::disk('public')->path($favicon), public_path('favicon.ico'));
        }
    }
}

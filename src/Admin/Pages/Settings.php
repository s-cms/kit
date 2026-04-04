<?php

namespace SmartCms\Kit\Admin\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use SmartCms\Kit\Actions\Support\RenameTranslationKey;
use SmartCms\Kit\Admin\Enums\NavigationGroup;
use SmartCms\Kit\Admin\Settings\BrandingForm;
use SmartCms\Kit\Admin\Settings\GeneralForm;
use SmartCms\Kit\Admin\Settings\NotificationForm;
use SmartCms\Kit\Admin\Settings\SeoForm;
use SmartCms\Kit\Admin\Settings\SystemForm;
use SmartCms\Lang\Models\Language;
use SmartCms\PanelSettings\SettingsPage;
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
                        SeoForm::make(),
                        NotificationForm::make(),
                        SystemForm::make(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public bool $pendingRename = false;

    public ?string $pendingOldLang = null;

    public ?string $pendingNewLang = null;

    public function save(): void
    {
        $data = $this->form->getState();
        if (! $data['is_multi_lang']) {
            $data['additional_languages'] = [];
            $data['front_languages'] = [];
        }
        $this->form->fill($data);

        $oldDefaultLang = main_lang();
        $newDefaultLangId = $data['main_language'];
        $newDefaultLang = Language::query()->where('id', $newDefaultLangId)->value('slug');
        $isMultiLang = (bool) $data['is_multi_lang'];
        $defaultLanguageChanged = $newDefaultLangId != main_lang_id();

        if ($defaultLanguageChanged && ! $isMultiLang) {
            $renameAction = new RenameTranslationKey;

            if ($renameAction->needsRename($oldDefaultLang, $newDefaultLang)) {
                $this->pendingRename = true;
                $this->pendingOldLang = $oldDefaultLang;
                $this->pendingNewLang = $newDefaultLang;
                $this->mountAction('confirmRenameTranslationKey');

                return;
            }
        }

        $this->persistSettings($data, $newDefaultLangId);
    }

    public function confirmRenameTranslationKeyAction(): Action
    {
        return Action::make('confirmRenameTranslationKey')
            ->requiresConfirmation()
            ->modalHeading(__('kit::admin.rename_translation_key_heading'))
            ->modalDescription(__('kit::admin.rename_translation_key_description', [
                'old' => $this->pendingOldLang ?? '',
                'new' => $this->pendingNewLang ?? '',
            ]))
            ->modalSubmitActionLabel(__('kit::admin.rename_translation_key_confirm'))
            ->action(function (): void {
                RenameTranslationKey::run($this->pendingOldLang, $this->pendingNewLang);

                $data = $this->form->getState();
                if (! $data['is_multi_lang']) {
                    $data['additional_languages'] = [];
                    $data['front_languages'] = [];
                }
                $newDefaultLangId = $data['main_language'];
                $this->persistSettings($data, $newDefaultLangId);
                $this->resetPendingRename();
            })
            ->modalCancelAction(fn (Action $action) => $action->action(fn () => $this->resetPendingRename()));
    }

    protected function persistSettings(array $data, int $newDefaultLangId): void
    {
        parent::save();
        $this->syncLanguages($newDefaultLangId, $data);
    }

    protected function resetPendingRename(): void
    {
        $this->pendingRename = false;
        $this->pendingOldLang = null;
        $this->pendingNewLang = null;
    }

    protected function syncLanguages(int $newDefaultLangId, array $data): void
    {
        Language::query()
            ->where('id', $newDefaultLangId)
            ->update([
                'is_default' => true,
                'is_admin_active' => true,
                'is_frontend_active' => true,
            ]);
        Language::query()
            ->where('id', '!=', $newDefaultLangId)
            ->update([
                'is_default' => false,
                'is_admin_active' => false,
                'is_frontend_active' => false,
            ]);
        Language::query()
            ->where('is_default', false)
            ->whereIn('id', $data['additional_languages'] ?? [])
            ->update([
                'is_admin_active' => true,
                'is_frontend_active' => false,
            ]);
        Language::query()
            ->where('is_default', false)
            ->whereIn('id', $data['front_languages'] ?? [])
            ->update([
                'is_admin_active' => true,
                'is_frontend_active' => true,
            ]);
    }
}

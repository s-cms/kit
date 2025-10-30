<?php

namespace SmartCms\Kit\Admin\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use SmartCms\Kit\Admin\Clusters\Design\DesignCluster;
use SmartCms\Kit\Admin\Clusters\System\SystemCluster;
use SmartCms\Kit\Admin\Settings\BrandingForm;
use SmartCms\Kit\Admin\Settings\CompanyInfoForm;
use SmartCms\Kit\Admin\Settings\GeneralForm;
use SmartCms\Kit\Admin\Settings\NotificationForm;
use SmartCms\Kit\Admin\Settings\SeoForm;
use SmartCms\Kit\Admin\Settings\SystemForm;
use SmartCms\Kit\Admin\Settings\ThemeForm;
use SmartCms\Kit\Models\Block;
use SmartCms\Lang\Models\Language;
use SmartCms\PanelSettings\SettingsPage;
use SmartCms\Support\Admin\Components\Actions\HelpAction;

/**
 * @property mixed $form
 */
class Layout extends SettingsPage
{
    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.layout');
    }

    public static function getCluster(): ?string
    {
        return DesignCluster::class;
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
                Section::make()->schema([
                    Repeater::make('header_blocks')->label(__('kit::admin.header_blocks'))->simple(Select::make('id')->options(Block::query()->pluck('title', 'id')))->required()
                        ->addActionAlignment(Alignment::End)
                        ->collapseAllAction(
                            fn(Action $action) => $action->hidden(),
                        )
                        ->expandAllAction(
                            fn(Action $action) => $action->hidden(),
                        ),
                    Repeater::make('footer_blocks')->label(__('kit::admin.footer_blocks'))->simple(Select::make('id')->options(Block::query()->pluck('title', 'id')))->required()->addActionAlignment(Alignment::End)
                        ->collapseAllAction(
                            fn(Action $action) => $action->hidden(),
                        )
                        ->expandAllAction(
                            fn(Action $action) => $action->hidden(),
                        ),
                ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

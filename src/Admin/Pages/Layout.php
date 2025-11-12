<?php

namespace SmartCms\Kit\Admin\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use SmartCms\Kit\Models\Block;
use SmartCms\PanelSettings\SettingsPage;
use SmartCms\Kit\Admin\Enums\NavigationGroup;
use UnitEnum;

/**
 * @property mixed $form
 */
class Layout extends SettingsPage
{
    protected static ?int $navigationSort = 0;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::Cog6Tooth;

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.layout');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return NavigationGroup::Design;
    }

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
                            fn (Action $action) => $action->hidden(),
                        )
                        ->expandAllAction(
                            fn (Action $action) => $action->hidden(),
                        ),
                    Repeater::make('footer_blocks')->label(__('kit::admin.footer_blocks'))->simple(Select::make('id')->options(Block::query()->pluck('title', 'id')))->required()->addActionAlignment(Alignment::End)
                        ->collapseAllAction(
                            fn (Action $action) => $action->hidden(),
                        )
                        ->expandAllAction(
                            fn (Action $action) => $action->hidden(),
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

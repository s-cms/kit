<?php

namespace SmartCms\Kit\Admin\Resources\Pages\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Group;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use SmartCms\Kit\Actions\Admin\GetPageListUrl;
use SmartCms\Kit\Admin\Resources\Pages\PageResource;
use SmartCms\Kit\Models\Admin;
use SmartCms\Kit\Models\Page;
use SmartCms\Support\Admin\Components\Actions\SaveAction;
use SmartCms\Support\Admin\Components\Actions\SaveAndClose;
use SmartCms\Support\Admin\Components\Actions\ViewRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    public function getTitle(): string
    {
        return __('kit::admin.edit_page') . ' ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                SaveAction::make($this),
                SaveAndClose::make($this, GetPageListUrl::run($this->getRecord())),
                ViewRecord::make(),
                DeleteAction::make()->hidden(fn (Page $record): bool => $record->is_system || $record->is_root),
                Action::make('show info')->label(__('kit::admin.show_info'))->icon(Heroicon::InformationCircle)->color('primary')->schema([
                    Group::make([
                        TextEntry::make('created_at')->icon(Heroicon::OutlinedClock)->date(),
                        TextEntry::make('created_by')->icon(Heroicon::UserCircle)->formatStateUsing(function ($state) {
                            $admin = Admin::query()->find($state);

                            return $admin?->name ?? __('kit::admin.system');
                        }),
                        TextEntry::make('updated_at')->icon(Heroicon::OutlinedClock)->date(),
                        TextEntry::make('updated_by')->icon(Heroicon::OutlinedUserCircle)->formatStateUsing(function ($state) {
                            $admin = Admin::query()->find($state);

                            return $admin?->name ?? __('kit::admin.system');
                        }),
                    ])->columns(2),
                ]),
            ])->link()->label('Actions')
                ->icon(Heroicon::ChevronDown)
                ->size(Size::Small)
                ->iconPosition(IconPosition::After)
                ->color('primary'),

        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.edit');
    }
}

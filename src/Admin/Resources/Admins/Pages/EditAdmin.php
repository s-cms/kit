<?php

namespace SmartCms\Kit\Admin\Resources\Admins\Pages;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use SmartCms\Kit\Admin\Resources\Admins\AdminResource;
use SmartCms\Support\Admin\Components\Actions\SaveAction;
use SmartCms\Support\Admin\Components\Actions\SaveAndClose;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                SaveAction::make($this),
                SaveAndClose::make($this, AdminResource::getUrl('index')),
                DeleteAction::make()->hidden(fn ($record): bool => $record->id == 1),
            ])->link()->label(__('kit::admin.actions'))
                ->icon(Heroicon::ChevronDown)
                ->size(Size::Small)
                ->iconPosition(IconPosition::After)
                ->color('primary'),
        ];
    }
}

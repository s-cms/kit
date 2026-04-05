<?php

namespace SmartCms\Kit\Admin\Resources\Media\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use SmartCms\Kit\Admin\Resources\Media\MediaResource;
use SmartCms\Support\Admin\Components\Actions\SaveAction;
use SmartCms\Support\Admin\Components\Actions\SaveAndClose;

class EditMedia extends EditRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                SaveAction::make($this),
                SaveAndClose::make($this, MediaResource::getUrl('index')),
                Action::make('download')
                    ->label(__('kit::admin.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => $record->getUrl(), shouldOpenInNewTab: true),
                DeleteAction::make(),
            ])->link()->label(__('kit::admin.actions'))
                ->icon(Heroicon::ChevronDown)
                ->size(Size::Small)
                ->iconPosition(IconPosition::After)
                ->color('primary'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle alt text saving through custom property
        if (isset($data['alt'])) {
            $this->record->setCustomProperty('alt', $data['alt']);
            unset($data['alt']);
        }

        return $data;
    }
}

<?php

namespace SmartCms\Kit\Admin\Resources\Media\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use SmartCms\Kit\Admin\Resources\Media\MediaResource;
use SmartCms\Support\Admin\Components\Actions\SaveAction;
use SmartCms\Support\Admin\Components\Actions\SaveAndClose;

class EditMedia extends EditRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ActionGroup::make([
                SaveAction::make($this),
                SaveAndClose::make($this, MediaResource::getUrl('index')),
                Action::make('download')
                    ->label(__('kit::admin.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => $record->getUrl(), shouldOpenInNewTab: true),
                DeleteAction::make(),
            ])->link()->label(__('kit::admin.actions'))
                ->icon(\Filament\Support\Icons\Heroicon::ChevronDown)
                ->size(\Filament\Support\Enums\Size::Small)
                ->iconPosition(\Filament\Support\Enums\IconPosition::After)
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

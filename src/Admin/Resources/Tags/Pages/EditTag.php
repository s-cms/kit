<?php

namespace SmartCms\Kit\Admin\Resources\Tags\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use SmartCms\Kit\Admin\Resources\Tags\TagResource;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

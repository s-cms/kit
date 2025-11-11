<?php

namespace SmartCms\Kit\Admin\Resources\BlockTemplates\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use SmartCms\Kit\Admin\Resources\BlockTemplates\BlockTemplateResource;

class EditBlockTemplate extends EditRecord
{
    protected static string $resource = BlockTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

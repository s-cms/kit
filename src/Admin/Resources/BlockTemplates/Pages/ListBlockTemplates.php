<?php

namespace SmartCms\Kit\Admin\Resources\BlockTemplates\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use SmartCms\Kit\Admin\Resources\BlockTemplates\BlockTemplateResource;

class ListBlockTemplates extends ListRecords
{
    protected static string $resource = BlockTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace SmartCms\Kit\Admin\Resources\Blocks\Pages;

use SmartCms\Kit\Admin\Resources\Blocks\BlockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlocks extends ListRecords
{
    protected static string $resource = BlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

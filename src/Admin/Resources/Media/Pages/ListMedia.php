<?php

namespace SmartCms\Kit\Admin\Resources\Media\Pages;

use Filament\Resources\Pages\ListRecords;
use SmartCms\Kit\Admin\Resources\Media\MediaResource;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Upload action will be handled via MediaPicker component
        ];
    }
}

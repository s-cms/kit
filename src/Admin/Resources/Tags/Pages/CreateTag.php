<?php

namespace SmartCms\Kit\Admin\Resources\Tags\Pages;

use Filament\Resources\Pages\CreateRecord;
use SmartCms\Kit\Admin\Resources\Tags\TagResource;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;
}

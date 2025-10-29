<?php

namespace SmartCms\Kit\Admin\Resources\Blocks\Pages;

use SmartCms\Kit\Admin\Resources\Blocks\BlockResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBlock extends CreateRecord
{
    protected static string $resource = BlockResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['schema'] = [];
        return parent::handleRecordCreation($data);
    }
}

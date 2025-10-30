<?php

namespace SmartCms\Kit\Admin\Resources\Blocks\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use SmartCms\Kit\Admin\Clusters\Design\DesignCluster;
use SmartCms\Kit\Admin\Resources\Blocks\BlockResource;

class CreateBlock extends CreateRecord
{
    protected static string $resource = BlockResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['schema'] = [];

        return parent::handleRecordCreation($data);
    }

    public function getSubNavigation(): array
    {
        return app(DesignCluster::class)->getSubNavigation();
    }
}

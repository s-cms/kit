<?php

namespace SmartCms\Kit\Admin\Resources\Blocks\Pages;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use SmartCms\Kit\Admin\Clusters\Design\DesignCluster;
use SmartCms\Kit\Admin\Resources\Blocks\BlockResource;
use SmartCms\Support\Admin\Components\Actions\SaveAction;
use SmartCms\Support\Admin\Components\Actions\SaveAndClose;

class EditBlock extends EditRecord
{
    protected static string $resource = BlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                SaveAction::make($this),
                SaveAndClose::make($this, ListBlocks::getUrl()),
                DeleteAction::make(),
            ]),
        ];
    }

    public function getSubNavigation(): array
    {
        return app(DesignCluster::class)->getSubNavigation();
    }

    // protected function handleRecordUpdate(Model $record, array $data): Model
    // {
    //     dd($data, $record);
    // }
}

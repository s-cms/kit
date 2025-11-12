<?php

namespace SmartCms\Kit\Admin\Resources\BlockTemplates\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $blocksData = $data['blocks'] ?? [];
        unset($data['blocks']);

        // Detach all existing blocks first to allow duplicates
        $record->blocks()->detach();

        // Attach each block individually to preserve duplicates
        // Use array index as sorting order for reorderable repeater
        foreach ($blocksData as $index => $blockData) {
            $blockId = $blockData['block_id'] ?? null;
            if ($blockId) {
                $record->blocks()->attach($blockId, [
                    'status' => $blockData['status'] ?? true,
                    'sorting' => $index + 1,
                    'show_from' => $blockData['show_from'] ?? null,
                    'show_until' => $blockData['show_until'] ?? null,
                ]);
            }
        }

        return parent::handleRecordUpdate($record, $data);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['blocks'] = $this->record->blocks->map(function ($block) {
            $pivot = $block->pivot;

            return [
                'block_id' => $block->id,
                'status' => $pivot->status,
                'sorting' => $pivot->sorting,
                'show_from' => $pivot->show_from,
                'show_until' => $pivot->show_until,
            ];
        })->toArray();

        return $data;
    }
}

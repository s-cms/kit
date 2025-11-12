<?php

namespace SmartCms\Kit\Admin\Resources\BlockTemplates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use SmartCms\Kit\Admin\Resources\BlockTemplates\BlockTemplateResource;

class CreateBlockTemplate extends CreateRecord
{
    protected static string $resource = BlockTemplateResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $blocksData = $data['blocks'] ?? [];
        unset($data['blocks']);

        $record = parent::handleRecordCreation($data);

        // Attach each block individually to preserve duplicates
        // Use array index as sorting order for reorderable repeater
        foreach ($blocksData as $index => $blockData) {
            $blockId = $blockData['block_id'] ?? null;
            if ($blockId) {
                $record->blocks()->attach($blockId, [
                    'status' => $blockData['status'] ?? true,
                    'sorting' => $blockData['sorting'] ?? $index,
                    'show_from' => $blockData['show_from'] ?? null,
                    'show_until' => $blockData['show_until'] ?? null,
                ]);
            }
        }

        return $record;
    }
}

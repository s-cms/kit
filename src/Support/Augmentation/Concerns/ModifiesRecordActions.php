<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

trait ModifiesRecordActions
{
    /**
     * Get admin table record actions (Filament 4).
     * Actions that appear on each table row.
     *
     * @return array<\Filament\Tables\Actions\Action>
     */
    public static function getRecordActions(): array
    {
        return [];
    }
}

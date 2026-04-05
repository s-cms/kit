<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

use Filament\Tables\Actions\Action;

trait ModifiesRecordActions
{
    /**
     * Get admin table record actions (Filament 4).
     * Actions that appear on each table row.
     *
     * @return array<Action>
     */
    public static function getRecordActions(): array
    {
        return [];
    }
}

<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

use Filament\Tables\Actions\Action;

trait ModifiesHeaderActions
{
    /**
     * Get admin table header actions (Filament 4).
     * Actions that appear in the table header.
     *
     * @return array<Action>
     */
    public static function getHeaderActions(): array
    {
        return [];
    }
}

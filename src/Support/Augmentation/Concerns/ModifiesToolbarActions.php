<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

use Filament\Tables\Actions\Action;

trait ModifiesToolbarActions
{
    /**
     * Get admin table toolbar actions (Filament 4).
     * Actions that appear in the table toolbar.
     *
     * @return array<Action>
     */
    public static function getToolbarActions(): array
    {
        return [];
    }
}

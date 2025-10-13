<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

trait ModifiesToolbarActions
{
    /**
     * Get admin table toolbar actions (Filament 4).
     * Actions that appear in the table toolbar.
     *
     * @return array<\Filament\Tables\Actions\Action>
     */
    public static function getToolbarActions(): array
    {
        return [];
    }
}

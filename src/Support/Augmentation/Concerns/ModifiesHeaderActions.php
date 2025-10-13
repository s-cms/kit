<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

trait ModifiesHeaderActions
{
    /**
     * Get admin table header actions (Filament 4).
     * Actions that appear in the table header.
     *
     * @return array<\Filament\Tables\Actions\Action>
     */
    public static function getHeaderActions(): array
    {
        return [];
    }
}

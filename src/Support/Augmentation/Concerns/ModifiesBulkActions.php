<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

trait ModifiesBulkActions
{
    /**
     * Get admin table bulk actions.
     *
     * @return array<\Filament\Tables\Actions\BulkAction>
     */
    public static function getBulkActions(): array
    {
        return [];
    }
}

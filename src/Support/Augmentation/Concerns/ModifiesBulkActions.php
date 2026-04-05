<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

use Filament\Tables\Actions\BulkAction;

trait ModifiesBulkActions
{
    /**
     * Get admin table bulk actions.
     *
     * @return array<BulkAction>
     */
    public static function getBulkActions(): array
    {
        return [];
    }
}

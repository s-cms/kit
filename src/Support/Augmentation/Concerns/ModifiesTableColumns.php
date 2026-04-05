<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

use Filament\Tables\Columns\Column;

trait ModifiesTableColumns
{
    /**
     * Get admin table columns.
     *
     * @return array<Column>
     */
    public static function getColumns(): array
    {
        return [];
    }
}

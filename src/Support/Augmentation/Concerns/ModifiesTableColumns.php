<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

trait ModifiesTableColumns
{
    /**
     * Get admin table columns.
     *
     * @return array<\Filament\Tables\Columns\Column>
     */
    public static function getColumns(): array
    {
        return [];
    }
}

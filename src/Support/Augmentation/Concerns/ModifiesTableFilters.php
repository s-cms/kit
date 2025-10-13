<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

trait ModifiesTableFilters
{
    /**
     * Get admin table filters.
     *
     * @return array<\Filament\Tables\Filters\Filter>
     */
    public static function getFilters(): array
    {
        return [];
    }
}

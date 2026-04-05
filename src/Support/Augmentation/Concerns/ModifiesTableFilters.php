<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

use Filament\Tables\Filters\Filter;

trait ModifiesTableFilters
{
    /**
     * Get admin table filters.
     *
     * @return array<Filter>
     */
    public static function getFilters(): array
    {
        return [];
    }
}

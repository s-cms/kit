<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

use Filament\Forms\Components\Component;

trait ModifiesFormSchema
{
    /**
     * Get admin form schema components.
     *
     * @return array<Component>
     */
    public static function getSchema(): array
    {
        return [];
    }
}

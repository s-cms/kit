<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

trait ModifiesFormSchema
{
    /**
     * Get admin form schema components.
     *
     * @return array<\Filament\Forms\Components\Component>
     */
    public static function getSchema(): array
    {
        return [];
    }
}

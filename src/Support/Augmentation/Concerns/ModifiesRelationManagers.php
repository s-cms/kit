<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

trait ModifiesRelationManagers
{
    /**
     * Get relation managers for Filament resource.
     *
     * @return array<class-string<\Filament\Resources\RelationManagers\RelationManager>>
     */
    public static function getRelationManagers(): array
    {
        return [];
    }
}

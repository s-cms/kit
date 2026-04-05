<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

use Filament\Resources\RelationManagers\RelationManager;

trait ModifiesRelationManagers
{
    /**
     * Get relation managers for Filament resource.
     *
     * @return array<class-string<RelationManager>>
     */
    public static function getRelationManagers(): array
    {
        return [];
    }
}

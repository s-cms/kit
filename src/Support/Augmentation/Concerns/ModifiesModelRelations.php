<?php

namespace SmartCms\Kit\Support\Augmentation\Concerns;

trait ModifiesModelRelations
{
    /**
     * Get model relationships.
     *
     * @return array<string, \Closure>
     */
    public static function getRelations(): array
    {
        return [];
    }
}

<?php

namespace SmartCms\Kit\Support\Augmentation;

use SmartCms\Kit\Support\Transformers\TransformContext;

abstract class AbstractAugmentation
{
    /**
     * Transform page data for frontend serialization.
     * This method is ALWAYS available - it's the core purpose of augmentation.
     *
     * @param  TransformContext  $context
     */
    public static function transform($context): void
    {
        // Override in child class if needed
    }
}

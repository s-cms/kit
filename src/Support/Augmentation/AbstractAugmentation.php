<?php

namespace SmartCms\Kit\Support\Augmentation;

abstract class AbstractAugmentation
{
    /**
     * Transform page data for frontend serialization.
     * This method is ALWAYS available - it's the core purpose of augmentation.
     *
     * @param  \SmartCms\Kit\Support\Transformers\TransformContext  $context
     */
    public static function transform($context): void
    {
        // Override in child class if needed
    }
}

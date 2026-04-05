<?php

namespace SmartCms\Kit\Support\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Models\Blockable;

trait HasBlocks
{
    /**
     * Get all blocks associated with this model.
     * This uses morphToMany because blocks can be attached to multiple entities
     * and entities can have multiple blocks through the blockables pivot table.
     */
    public function blocks(): MorphToMany
    {
        return $this->morphToMany(
            Block::class,
            'blockable',
            'blockables'
        )
            ->using(Blockable::class)
            ->withPivot(['status', 'show_from', 'show_until', 'sorting', 'id'])
            ->withTimestamps()
            ->orderBy('sorting');
    }

    /**
     * Get only active blocks for this model.
     * Active blocks are those that:
     * - Have status = true
     * - Are within their show_from/show_until period (if specified)
     */
    public function activeBlocks(): MorphToMany
    {
        $now = now();

        return $this->blocks()
            ->wherePivot('status', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('blockables.show_from')
                    ->orWhere('blockables.show_from', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('blockables.show_until')
                    ->orWhere('blockables.show_until', '>=', $now);
            });
    }

    /**
     * Get active blocks as a collection.
     * This is a helper method that executes the query and returns results.
     *
     * @return Collection
     */
    public function getActiveBlocks()
    {
        return $this->activeBlocks()->get();
    }
}

<?php

namespace SmartCms\Kit\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Blockable extends MorphPivot
{
    protected $table = 'blockables';

    public $incrementing = true;

    protected $casts = [
        'status' => 'boolean',
        'show_from' => 'datetime',
        'show_until' => 'datetime',
    ];

    /**
     * Get the block associated with this relationship.
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    /**
     * Get the blockable model (Page, Product, Category, etc.).
     */
    public function blockable()
    {
        return $this->morphTo();
    }

    /**
     * Check if this block attachment is currently active.
     */
    public function isActive(): bool
    {
        if (!$this->status) {
            return false;
        }

        $now = now();

        if ($this->show_from && $this->show_from->isAfter($now)) {
            return false;
        }

        if ($this->show_until && $this->show_until->isBefore($now)) {
            return false;
        }

        return true;
    }

    /**
     * Scope to get only active block attachments.
     */
    public function scopeActive($query)
    {
        $now = now();

        return $query
            ->where('status', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('show_from')
                    ->orWhere('show_from', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('show_until')
                    ->orWhere('show_until', '>=', $now);
            });
    }
}

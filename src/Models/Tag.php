<?php

namespace SmartCms\Kit\Models;

use Spatie\Tags\Tag as BaseTag;

/**
 * Custom Tag model with translatable name and slug
 *
 * @property int $id
 * @property array $name Translatable tag name
 * @property array $slug Translatable tag slug
 * @property string|null $type Tag type/group
 * @property int|null $order_column Sort order
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Tag extends BaseTag
{

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'order_column',
    ];

    // /**
    //  * Get the table associated with the model.
    //  */
    // public function getTable(): string
    // {
    //     return config('tags.table_names.tags', 'tags');
    // }

}

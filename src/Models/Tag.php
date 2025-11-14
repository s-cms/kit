<?php

namespace SmartCms\Kit\Models;

use Spatie\Tags\Tag as BaseTag;
use Spatie\Translatable\HasTranslations;

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
    use HasTranslations;

    /**
     * The attributes that are translatable.
     */
    public array $translatable = ['name', 'slug'];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'order_column',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('tags.table_names.tags', 'tags');
    }

    /**
     * Override findFromString to handle translatable names
     */
    public static function findFromString(string $name, ?string $type = null, ?string $locale = null): self
    {
        $locale = $locale ?? app()->getLocale();

        return static::query()
            ->where("name->{$locale}", $name)
            ->when($type !== null, fn ($query) => $query->where('type', $type))
            ->first() ?? static::create([
                'name' => [$locale => $name],
                'type' => $type,
            ]);
    }

    /**
     * Override findOrCreate to handle translatable names
     */
    public static function findOrCreate(
        string | array | iterable $values,
        ?string $type = null,
        ?string $locale = null
    ): \Illuminate\Support\Collection | self | static {
        $tags = collect($values)->map(function ($value) use ($type, $locale) {
            if ($value instanceof self) {
                return $value;
            }

            return static::findFromString($value, $type, $locale);
        });

        return is_string($values) ? $tags->first() : $tags;
    }
}

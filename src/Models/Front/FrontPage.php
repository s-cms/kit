<?php

namespace SmartCms\Kit\Models\Front;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use SmartCms\Kit\Casts\ImageCast;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Augmentation\HasAugmentations;
use SmartCms\Kit\Support\Contracts\PageStatus;

class FrontPage extends Page
{
    use HasAugmentations;

    public static $staticCasts = [
        'settings' => 'array',
        'metadata' => 'array',
        // 'image' => ImageCast::class,
        // 'banner' => ImageCast::class,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('published_only', function (Builder $builder): void {
            $builder->where('status', PageStatus::Published->value);
        });
    }

    public static function addDynamicCast($attribute, $cast): void
    {
        self::$staticCasts[$attribute] = $cast;
    }

    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), $this->casts, self::$staticCasts);
    }

    /**
     * Get direct child categories of this page.
     * Only returns categories (type='category') that are direct children.
     */
    public function categories(): Attribute
    {
        return new Attribute(
            get: function () {
                // Only categories can have category children
                if (!$this->canHaveChildren()) {
                    return FrontPage::query()->where('id', 0);
                }

                // Return direct child categories
                return FrontPage::query()->where('parent_id', $this->id)->where('type', 'category');
            },
        );
    }

    /**
     * Get all child items (pages/posts) of this page.
     * Returns direct children that are NOT categories.
     */
    public function items(): Attribute
    {
        return new Attribute(
            get: function () {
                // Return direct children that are not categories
                return FrontPage::query()->where('parent_id', $this->id)->where('type', '!=', 'category');
            },
        );
    }

    /**
     * Get all descendants (children at any level).
     */
    public function allDescendants(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->descendants();
            },
        );
    }

    public function breadcrumbs(): Attribute
    {
        return new Attribute(get: fn(): array => $this->getBreadcrumbs());
    }

    public function url(): Attribute
    {
        return new Attribute(
            get: fn(): array => [
                'title' => $this->name,
                'is_external' => false,
                'url' => $this->route(),
            ],
        );
    }

    /**
     * Define keys for shallow augmentation (Inertia serialization).
     */
    public function shallowAugmentedArrayKeys(): array
    {
        return ['id', 'name', 'slug', 'type', 'url', 'breadcrumbs', 'image', 'banner', 'title', 'heading', 'summary'];
    }
}

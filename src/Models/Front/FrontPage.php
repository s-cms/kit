<?php

namespace SmartCms\Kit\Models\Front;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use SmartCms\Kit\Casts\ImageCast;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;

class FrontPage extends Page
{
    public static $staticCasts = [
        // maybe reserved word, doesnt work without it
        'settings' => 'array',
    ];

    protected $casts = [
        'image' => ImageCast::class,
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
        return array_merge(self::$staticCasts, $this->casts, parent::getCasts());
    }

    // public function heading(): Attribute
    // {
    //     return new Attribute(
    //         get: fn() => $this->heading ?? $this?->name ?? '',
    //     );
    // }

    // public function summary(): Attribute
    // {
    //     dd($this);
    //     return new Attribute(
    //         get: fn() => $this->summary ?? $this?->name ?? '',
    //     );
    // }

    // public function content(): Attribute
    // {
    //     dd($this->getTranslation('content', config('app.locale')));
    //     return new Attribute(
    //         get: fn() => str($this->content ?? '')->toHtmlString(),
    //     );
    // }

    public function categories(): Attribute
    {
        return new Attribute(
            get: function () {
                if ($this->parent_id) {
                    return FrontPage::query()->where('id', 0);
                }
                $settings = $this->settings ?? [];
                $isCategories = $settings['is_categories'] ?? false;
                if (! $isCategories) {
                    return FrontPage::query()->where('id', 0);
                }

                return FrontPage::query()->where('root_id', $this->id)->where('parent_id', '=', $this->id);
            }
        );
    }

    public function items(): Attribute
    {
        return new Attribute(
            get: function () {
                $settings = $this->settings ?? [];
                $isCategories = $settings['is_categories'] ?? false;
                if (! $isCategories) {
                    return FrontPage::query()->where('root_id', $this->is_root ? $this->id : $this->root_id)->where('parent_id', $this->id);
                }

                return FrontPage::query()->where('root_id', $this->is_root ? $this->id : $this->root_id)->where('parent_id', '!=', $this->id);
            }
        );
    }

    public function breadcrumbs(): Attribute
    {
        return new Attribute(
            get: fn (): array => $this->getBreadcrumbs(),
        );
    }

    public function url(): Attribute
    {
        return new Attribute(
            get: fn (): array => [
                'title' => $this->name,
                'is_external' => false,
                'url' => $this->route(),
            ],
        );
    }
}

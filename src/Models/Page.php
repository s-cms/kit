<?php

namespace SmartCms\Kit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use SmartCms\Kit\Casts\PageStatusCast;
use SmartCms\Kit\Components\PageComponent;
use SmartCms\Kit\Support\Augmentation\HasAugmentations;
use SmartCms\Support\Traits\HasBreadcrumbs;
use SmartCms\Support\Traits\HasParent;
use SmartCms\Support\Traits\HasRoute;
use SmartCms\Support\Traits\HasSlug;
use SmartCms\Support\Traits\HasSorting;
use SmartCms\Support\Traits\HasStatus;
use SmartCms\TemplateBuilder\Traits\HasLayout;
use SmartCms\TemplateBuilder\Traits\HasTemplate;
use Spatie\Translatable\HasTranslations;

/**
 * Class Page
 *
 * @property int $id The unique identifier for the model.
 * @property string $name The name of the page.
 * @property string $slug The slug of the page for URLs.
 * @property bool $status The status of the page.
 * @property int $sorting The sorting order of the page.
 * @property array|null $image The image path for the page.
 * @property array|null $banner The banner path for the page.
 * @property int $views The number of page views.
 * @property int $depth The depth of the page.
 * @property int|null $parent_id The parent page identifier.
 * @property int|null $root_id The root page identifier.
 * @property array|null $settings Settings for the page.
 * @property int|null $layout_id The layout identifier.
 * @property array|null $layout_settings Layout-specific settings.
 * @property array|null $settings Settings for the page.
 * @property bool $is_system Is system page.
 * @property bool $is_root Is hidden page.
 * @property \DateTime $created_at The date and time when the model was created.
 * @property \DateTime $updated_at The date and time when the model was last updated.
 * @property \DateTime $published_at The date and time when the model was published.
 * @property int $created_by The user who created the model.
 * @property int $updated_by The user who updated the model.
 * @property bool $is_index Is index page.
 * @property-read \SmartCms\TemplateBuilder\Models\Layout|null $layout The layout used by this page.
 * @property-read \SmartCms\Kit\Models\Page|null $parent The parent page.
 * @property-read \SmartCms\Kit\Models\Page|null $root The root page.
 * @property-read array $breadcrumbs The breadcrumbs for this page.
 */
class Page extends Model
{
    use HasAugmentations;
    use HasBreadcrumbs;
    use HasFactory;
    use HasLayout;
    use HasParent;
    use HasRoute;

    // use HasSorting;
    use HasSlug;
    use HasStatus;
    use HasTemplate;
    use HasTranslations;

    protected $guarded = [];

    public array $translatable = [
        'name',
        'layout_settings',
        'title',
        'heading',
        'summary',
        'content',
        'description',
        'keywords',
    ];

    protected $casts = [
        'status' => PageStatusCast::class,
        'settings' => 'array',
        'layout_settings' => 'array',
        'image' => 'array',
        'banner' => 'array',
        'published_at' => 'datetime',
        'title' => 'array',
        'heading' => 'array',
        'summary' => 'array',
        'content' => 'array',
        'description' => 'array',
        'keywords' => 'array',
    ];

    public function getBreadcrumbs(): array
    {
        return once(function (): array {
            $breadcrumbs = [
                [
                    'name' => $this->name,
                    'url' => [
                        'title' => $this->name,
                        'is_external' => false,
                        'url' => $this->route(),
                    ],
                ],
            ];
            if ($this->parent_id) {
                $parent = $this->getCachedParent();
                if ($parent) {
                    $breadcrumbs = array_merge($parent->getBreadcrumbs(), $breadcrumbs);
                }
            }

            return $breadcrumbs;
        });
    }

    public function route(): string
    {
        return once(function (): string {
            $slugs = [];
            $current = $this;

            while ($current) {
                array_unshift($slugs, $current->slug);
                $current = $current->getCachedParent();
            }

            return tRoute('cms.page', ['slug' => implode('/', $slugs)]);
        });
    }

    public function shouldGenerateSlug(): bool
    {
        return $this->id && $this->id !== 1;
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function root(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'root_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function (Page $page): void {
            $page->created_by = auth()?->id();
            $page->updated_by = auth()?->id();
        });
        static::created(function (Page $page): void {
            $template = app('s')->get('static_page_template', []);
            if ($page->root_id) {
                $root = Page::find($page->root_id);
                if (! $root) {
                    return;
                }
                $isCategory = false;
                if ($page->parent_id && $page->parent_id == $root->id && $root->settings['is_categories']) {
                    $isCategory = true;
                }
                $template = $isCategory ? $root->settings['categories_template'] ?? [] : $root->settings['items_template'] ?? [];
            }
            foreach ($template as $key => $item) {
                $page->template()->create([
                    'section_id' => $item['section_id'],
                    'sorting' => $key + 1,
                ]);
            }
            if ($page->sorting == 0) {
                $maxSorting = 0;
                if ($page->parent_id) {
                    $maxSorting = Page::query()->where('parent_id', $page->parent_id)->max('sorting');
                } else {
                    $maxSorting = Page::query()->max('sorting');
                }
                $page->sorting = $maxSorting + 1;
                $page->save();
            }
        });
        static::updating(function (Page $page): void {
            $page->updated_by = auth()?->id();
        });
    }

    public function getTable(): string
    {
        return config('kit.pages_table_name', 'pages');
    }

    public function render(): string
    {
        app()->instance('page', $this);

        return Blade::renderComponent(new PageComponent($this));
    }

    public function getFallbackLocale(): string
    {
        return main_lang();
    }
}

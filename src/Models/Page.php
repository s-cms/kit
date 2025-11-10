<?php

namespace SmartCms\Kit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use SmartCms\Kit\Casts\PageStatusCast;
use SmartCms\Kit\Components\PageComponent;
use SmartCms\Kit\Support\Augmentation\HasAugmentations;
use SmartCms\Kit\Support\Traits\HasBlocks;
use SmartCms\Support\Traits\HasBreadcrumbs;
use SmartCms\Support\Traits\HasParent;
use SmartCms\Support\Traits\HasRoute;
use SmartCms\Support\Traits\HasSlug;
use SmartCms\Support\Traits\HasSorting;
use SmartCms\Support\Traits\HasStatus;
use SmartCms\TemplateBuilder\Models\Layout;
use SmartCms\TemplateBuilder\Traits\HasLayout;
use SmartCms\TemplateBuilder\Traits\HasTemplate;
use Spatie\Translatable\HasTranslations;

/**
 * Class Page
 *
 * @property int $id The unique identifier for the model.
 * @property string $name The name of the page.
 * @property string $slug The slug of the page for URLs.
 * @property string $type The type of the page (page, category, or custom types).
 * @property bool $status The status of the page.
 * @property int $sorting The sorting order of the page.
 * @property array|null $image The image path for the page.
 * @property array|null $banner The banner path for the page.
 * @property int $views The number of page views.
 * @property int $depth The depth of the page.
 * @property int|null $parent_id The parent page identifier.
 * @property int|null $root_id The root page identifier (deprecated - use parent chain).
 * @property array|null $settings Settings for the page (deprecated - use metadata).
 * @property array|null $metadata Custom metadata for the page.
 * @property int|null $layout_id The layout identifier.
 * @property array|null $layout_settings Layout-specific settings.
 * @property bool $is_system Is system page.
 * @property bool $is_root Is hidden page (deprecated - use type).
 * @property \DateTime $created_at The date and time when the model was created.
 * @property \DateTime $updated_at The date and time when the model was last updated.
 * @property \DateTime $published_at The date and time when the model was published.
 * @property int $created_by The user who created the model.
 * @property int $updated_by The user who updated the model.
 * @property bool $is_index Is index page.
 * @property-read \SmartCms\TemplateBuilder\Models\Layout|null $layout The layout used by this page.
 * @property-read \SmartCms\Kit\Models\Page|null $parent The parent page.
 * @property-read \SmartCms\Kit\Models\Page|null $root The root page (deprecated).
 * @property-read array $breadcrumbs The breadcrumbs for this page.
 * @property-read \Illuminate\Database\Eloquent\Collection $children Child pages.
 */
class Page extends Model
{
    use HasAugmentations;
    use HasBlocks;
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

    /**
     * The page type for this model.
     * Override in subclasses to define custom types.
     */
    protected static ?string $pageType = null;

    /**
     * Whether this page type can have children.
     * Override in subclasses to define behavior.
     */
    protected static ?bool $canHaveChildren = null;

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
        'metadata' => 'array',
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

    /**
     * Check if this page type can have children.
     */
    public function canHaveChildren(): bool
    {
        // If defined in subclass, use that
        if (static::$canHaveChildren !== null) {
            return static::$canHaveChildren;
        }

        // Default behavior based on type
        return in_array($this->type, ['category', 'division']);
    }

    /**
     * Get the page type for this model.
     */
    public static function getPageType(): string
    {
        return static::$pageType ?? 'page';
    }

    /**
     * Get all ancestors (parents, grandparents, etc.)
     */
    public function ancestors(): \Illuminate\Support\Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors->reverse();
    }

    /**
     * Get all descendants (children, grandchildren, etc.)
     */
    public function descendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }

        return $descendants;
    }

    /**
     * Get the depth of this page (how many levels deep).
     */
    public function getDepth(): int
    {
        return $this->ancestors()->count();
    }

    /**
     * Validate that the page doesn't exceed maximum depth.
     */
    protected function validateDepth(): void
    {
        $maxDepth = config('kit.max_page_depth', 5);
        $currentDepth = $this->getDepth();

        if ($currentDepth >= $maxDepth) {
            throw new \Exception("Maximum nesting depth of {$maxDepth} levels exceeded.");
        }
    }

    /**
     * Validate that parent can have children.
     */
    protected function validateParentCanHaveChildren(): void
    {
        if ($this->parent_id && $this->parent) {
            if (! $this->parent->canHaveChildren()) {
                throw new \Exception("Parent page of type '{$this->parent->type}' cannot have children.");
            }
        }
    }

    protected static function boot()
    {
        parent::boot();

        // Add global scope for type filtering in subclasses
        static::addGlobalScope('type', function ($query) {
            if (static::$pageType !== null) {
                $query->where('type', static::getPageType());
            }
        });
        static::creating(function (Page $page): void {
            $page->created_by = auth()?->id();
            $page->updated_by = auth()?->id();

            // Auto-set type from $pageType if not already set
            if (empty($page->type) && static::$pageType !== null) {
                $page->type = static::getPageType();
            }

            // Default type to 'page' if still not set
            if (empty($page->type)) {
                $page->type = 'page';
            }

            // Validate depth (don't exceed max nesting)
            if ($page->parent_id) {
                $page->validateDepth();
                $page->validateParentCanHaveChildren();
            }

            // Auto-calculate depth
            $page->depth = $page->parent_id ? $page->getDepth() : 0;
        });
        static::created(function (Page $page): void {
            // Apply default template if defined
            $template = app('s')->get('static_page_template', []);
            foreach ($template as $key => $item) {
                $page->template()->create([
                    'section_id' => $item['section_id'],
                    'sorting' => $key + 1,
                ]);
            }

            // Auto-increment sorting if not set
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

        static::saving(function (Page $page): void {
            // Recalculate depth when parent changes
            if ($page->isDirty('parent_id')) {
                if ($page->parent_id) {
                    $page->validateDepth();
                    $page->validateParentCanHaveChildren();
                    $page->depth = $page->getDepth();
                } else {
                    $page->depth = 0;
                }
            }
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

    /**
     * Get available layouts for this page.
     * This method can be overridden in subclasses to filter layouts by type.
     */
    public function getAvailableLayouts(): array
    {
        return Layout::query()
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * @deprecated Use type system instead. Will be removed in v2.0.
     */
    protected function shouldUseDivisionLayout(): bool
    {
        return $this->type === 'category' || $this->is_root;
    }

    /**
     * @deprecated Use type system instead. Will be removed in v2.0.
     */
    protected function shouldUsePageLayout(): bool
    {
        return $this->type === 'page';
    }
}

<?php

namespace SmartCms\Kit\Actions\Pages;

use Illuminate\Database\Eloquent\Builder;
use SmartCms\Kit\Models\Page;

/**
 * Page Search Action
 *
 * Provides comprehensive search functionality for pages with support for:
 * - Text search (name, slug, content, description)
 * - Tag filtering
 * - Type filtering
 * - Status filtering
 * - Parent/child filtering
 * - Date range filtering
 *
 * Usage:
 * ```php
 * $pages = PageSearchAction::make()
 *     ->search('laravel')
 *     ->withTags(['tutorial', 'php'])
 *     ->ofType('page')
 *     ->published()
 *     ->get();
 * ```
 */
class PageSearchAction
{
    protected Builder $query;

    protected ?string $searchTerm = null;

    protected array $tags = [];

    protected ?string $type = null;

    protected ?string $status = null;

    protected ?int $parentId = null;

    protected ?string $dateFrom = null;

    protected ?string $dateTo = null;

    protected array $orderBy = ['updated_at', 'desc'];

    protected ?int $limit = null;

    public function __construct()
    {
        $this->query = Page::query();
    }

    /**
     * Create a new instance
     */
    public static function make(): static
    {
        return new self;
    }

    /**
     * Search by text (name, slug, content, description, keywords)
     */
    public function search(?string $term): static
    {
        if (empty($term)) {
            return $this;
        }

        $this->searchTerm = $term;

        $locale = app()->getLocale();

        $this->query->where(function (Builder $query) use ($term, $locale) {
            $query->where('slug', 'like', "%{$term}%")
                ->orWhere("name->{$locale}", 'like', "%{$term}%")
                ->orWhere("content->{$locale}", 'like', "%{$term}%")
                ->orWhere("description->{$locale}", 'like', "%{$term}%")
                ->orWhere("keywords->{$locale}", 'like', "%{$term}%")
                ->orWhere("summary->{$locale}", 'like', "%{$term}%");
        });

        return $this;
    }

    /**
     * Filter by tags
     *
     * @param  array|string  $tags  Tag names or IDs
     * @param  bool  $matchAll  If true, page must have ALL tags. If false, page must have ANY tag.
     */
    public function withTags(array | string $tags, bool $matchAll = false): static
    {
        if (empty($tags)) {
            return $this;
        }

        $tags = is_array($tags) ? $tags : [$tags];
        $this->tags = $tags;

        if ($matchAll) {
            // Must have ALL specified tags
            foreach ($tags as $tag) {
                $this->query->whereHas('tags', function (Builder $query) use ($tag) {
                    if (is_numeric($tag)) {
                        $query->where('tags.id', $tag);
                    } else {
                        $locale = app()->getLocale();
                        $query->where("tags.name->{$locale}", $tag)
                            ->orWhere("tags.slug->{$locale}", $tag);
                    }
                });
            }
        } else {
            // Must have ANY of the specified tags
            $this->query->whereHas('tags', function (Builder $query) use ($tags) {
                $query->where(function (Builder $q) use ($tags) {
                    $locale = app()->getLocale();
                    foreach ($tags as $tag) {
                        if (is_numeric($tag)) {
                            $q->orWhere('tags.id', $tag);
                        } else {
                            $q->orWhere("tags.name->{$locale}", $tag)
                                ->orWhere("tags.slug->{$locale}", $tag);
                        }
                    }
                });
            });
        }

        return $this;
    }

    /**
     * Filter by page type
     */
    public function ofType(string $type): static
    {
        $this->type = $type;
        $this->query->where('type', $type);

        return $this;
    }

    /**
     * Filter by status
     */
    public function withStatus(string $status): static
    {
        $this->status = $status;
        $this->query->where('status', $status);

        return $this;
    }

    /**
     * Only published pages
     */
    public function published(): static
    {
        return $this->withStatus('published');
    }

    /**
     * Only draft pages
     */
    public function draft(): static
    {
        return $this->withStatus('draft');
    }

    /**
     * Filter by parent page
     */
    public function childrenOf(?int $parentId): static
    {
        $this->parentId = $parentId;

        if ($parentId === null) {
            $this->query->whereNull('parent_id');
        } else {
            $this->query->where('parent_id', $parentId);
        }

        return $this;
    }

    /**
     * Only root level pages (no parent)
     */
    public function rootOnly(): static
    {
        return $this->childrenOf(null);
    }

    /**
     * Filter by date range
     */
    public function createdBetween(?string $from, ?string $to): static
    {
        if ($from) {
            $this->dateFrom = $from;
            $this->query->where('created_at', '>=', $from);
        }

        if ($to) {
            $this->dateTo = $to;
            $this->query->where('created_at', '<=', $to);
        }

        return $this;
    }

    /**
     * Order results
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->orderBy = [$column, $direction];
        $this->query->orderBy($column, $direction);

        return $this;
    }

    /**
     * Limit results
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;
        $this->query->limit($limit);

        return $this;
    }

    /**
     * Execute the search and return results
     */
    public function get()
    {
        return $this->query->get();
    }

    /**
     * Execute the search with pagination
     */
    public function paginate(int $perPage = 15)
    {
        return $this->query->paginate($perPage);
    }

    /**
     * Get the count of results
     */
    public function count(): int
    {
        return $this->query->count();
    }

    /**
     * Get first result
     */
    public function first(): ?Page
    {
        return $this->query->first();
    }

    /**
     * Get the underlying query builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * Get search parameters for debugging
     */
    public function getParameters(): array
    {
        return [
            'search_term' => $this->searchTerm,
            'tags' => $this->tags,
            'type' => $this->type,
            'status' => $this->status,
            'parent_id' => $this->parentId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'order_by' => $this->orderBy,
            'limit' => $this->limit,
        ];
    }
}

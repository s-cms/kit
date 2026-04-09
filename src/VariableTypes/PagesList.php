<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use SmartCms\Kit\Http\Resources\FrontPageResource;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class PagesList implements VariableTypeInterface
{
    public const int DEFAULT_LIMIT = 10;

    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'pages_list';
    }

    public function getDefaultValue(): mixed
    {
        return Page::query()
            ->where('status', PageStatus::Published->value)
            ->where('type', 'page')
            ->whereNotNull('parent_id')
            ->with('tags')
            ->limit(self::DEFAULT_LIMIT)
            ->get()
            ->map(fn (Page $item): array => (new FrontPageResource($item))->toArray(request()));
    }

    public function getSchema(string $name, ?string $language = null): Component
    {
        $lang = $language ?? main_lang();

        return Group::make([
            Select::make($name . '.type')
                ->label(__('kit::admin.pages_list_type'))
                ->options([
                    'articles' => __('kit::admin.articles'),
                    'categories' => __('kit::admin.categories'),
                ])
                ->default('articles')
                ->live()
                ->required(),

            Select::make($name . '.mode')
                ->label(__('kit::admin.pages_list_mode'))
                ->options([
                    'auto' => __('kit::admin.pages_list_mode_auto'),
                    'manual' => __('kit::admin.pages_list_mode_manual'),
                ])
                ->default('auto')
                ->live()
                ->required(),

            Select::make($name . '.parent_id')
                ->label(__('kit::admin.parent_category'))
                ->options(fn () => self::buildCategoryTreeOptions($lang))
                ->placeholder(__('kit::admin.all'))
                ->searchable()
                ->visible(fn (Get $get): bool => ($get($name . '.mode') ?? 'auto') === 'auto'),

            Select::make($name . '.page_ids')
                ->label(__('kit::admin.pages_list_select_pages'))
                ->options(fn (Get $get) => self::buildManualOptions($get($name . '.type') ?? 'articles', $lang))
                ->multiple()
                ->searchable()
                ->visible(fn (Get $get): bool => ($get($name . '.mode') ?? 'auto') === 'manual'),

            Select::make($name . '.sort')
                ->label(__('kit::admin.pages_list_sort'))
                ->options([
                    'default' => __('kit::admin.pages_list_sort_default'),
                    'latest' => __('kit::admin.pages_list_sort_latest'),
                    'popular' => __('kit::admin.pages_list_sort_popular'),
                    'random' => __('kit::admin.pages_list_sort_random'),
                ])
                ->default('default')
                ->required(),

            TextInput::make($name . '.limit')
                ->label(__('kit::admin.pages_list_limit'))
                ->numeric()
                ->default(self::DEFAULT_LIMIT)
                ->formatStateUsing(fn ($state) => $state ?? self::DEFAULT_LIMIT),
        ]);
    }

    public function getValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $this->getDefaultValue();
        }

        $type = $value['type'] ?? 'articles';
        $mode = $value['mode'] ?? 'auto';
        $sort = $value['sort'] ?? 'default';
        $limit = (int) ($value['limit'] ?? self::DEFAULT_LIMIT);

        $query = Page::query()
            ->where('status', PageStatus::Published->value)
            ->with('tags');

        if ($type === 'categories') {
            $query->where('type', 'category');
        } else {
            $query->where('type', 'page')->whereNotNull('parent_id');
        }

        if ($mode === 'manual') {
            $pageIds = $value['page_ids'] ?? [];
            if (empty($pageIds)) {
                return collect();
            }
            $query->whereIn('id', $pageIds);
        } else {
            $parentId = $value['parent_id'] ?? null;
            if ($parentId) {
                if ($type === 'articles') {
                    // Include the parent and all its descendants
                    $descendantIds = self::collectDescendantIds((int) $parentId);
                    $query->whereIn('parent_id', $descendantIds);
                } else {
                    $query->where('parent_id', $parentId);
                }
            }
        }

        match ($sort) {
            'latest' => $query->orderBy('published_at', 'desc')->orderBy('updated_at', 'desc'),
            'popular' => $query->orderBy('views', 'desc'),
            'random' => $query->inRandomOrder(),
            default => $query->orderBy('id'),
        };

        return $query
            ->limit($limit > 0 ? $limit : self::DEFAULT_LIMIT)
            ->get()
            ->map(fn (Page $item): array => (new FrontPageResource($item))->toArray(request()));
    }

    /**
     * Build a tree-shaped option list (with depth indents) for category select.
     *
     * @return array<int,string>
     */
    public static function buildCategoryTreeOptions(string $lang): array
    {
        $maxDepth = config('kit.max_page_depth', 5);

        $categories = Page::query()
            ->where('type', 'category')
            ->where('depth', '<', $maxDepth - 1)
            ->orderBy('sorting')
            ->get()
            ->groupBy(fn (Page $page) => $page->parent_id ?? 0);

        $options = [];
        self::appendCategoryTreeOption($categories, 0, 0, $lang, $options);

        return $options;
    }

    protected static function appendCategoryTreeOption($groups, $parentId, int $depth, string $lang, array &$options): void
    {
        $children = $groups->get($parentId, collect());
        foreach ($children as $page) {
            $indent = str_repeat('— ', $depth);
            $options[$page->id] = $indent . $page->getTranslation('name', $lang);
            self::appendCategoryTreeOption($groups, $page->id, $depth + 1, $lang, $options);
        }
    }

    /**
     * Build option list for manual selection — categories tree or articles flat.
     *
     * @return array<int,string>
     */
    protected static function buildManualOptions(string $type, string $lang): array
    {
        if ($type === 'categories') {
            return self::buildCategoryTreeOptions($lang);
        }

        return Page::query()
            ->where('type', 'page')
            ->whereNotNull('parent_id')
            ->orderBy('id', 'desc')
            ->get()
            ->mapWithKeys(fn (Page $page) => [$page->id => $page->getTranslation('name', $lang)])
            ->toArray();
    }

    /**
     * @return array<int>
     */
    protected static function collectDescendantIds(int $parentId): array
    {
        $ids = [$parentId];
        $stack = [$parentId];

        while (! empty($stack)) {
            $current = array_pop($stack);
            $children = Page::query()
                ->where('parent_id', $current)
                ->where('type', 'category')
                ->pluck('id')
                ->all();

            foreach ($children as $childId) {
                $ids[] = $childId;
                $stack[] = $childId;
            }
        }

        return $ids;
    }
}

<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use SmartCms\Kit\Http\Resources\FrontPageResource;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Models\Pages\SimplePage;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

/**
 * @deprecated since 1.x — will be removed in 2.0. Use \SmartCms\Kit\VariableTypes\PagesList instead.
 */
class LatestItems implements VariableTypeInterface
{
    public const int DEFAULT_LIMIT = 3;

    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'latest_items';
    }

    public function getDefaultValue(): mixed
    {
        return SimplePage::query()->with('tags')->limit(self::DEFAULT_LIMIT)->get()->map(fn ($item): array => (new FrontPageResource($item))->toArray(request()));
    }

    public function getSchema(string $name, ?string $language = null): Field | Component
    {
        $lang = $language ?? main_lang();

        return Group::make([
            Select::make($name . '.parent_id')
                ->label(__('kit::admin.parent_category'))
                ->options(Page::query()->where('type', 'category')->get()->mapWithKeys(fn (Page $page) => [$page->id => $page->getTranslation('name', $lang)]))
                ->required()
                ->live()
                ->helperText(__('kit::admin.select_parent_for_items')),
            Select::make($name . '.categories')
                ->label(__('kit::admin.filter_by_categories'))
                ->options(fn (Get $get) => Page::query()
                    ->where('type', 'category')
                    ->where('parent_id', $get($name . '.parent_id') ?? 0)
                    ->get()
                    ->mapWithKeys(fn (Page $page) => [$page->id => $page->getTranslation('name', $lang)]))
                ->live()
                ->multiple()
                ->visible(fn (Get $get) => $get($name . '.parent_id'))
                ->helperText(__('kit::admin.optional_category_filter')),
            TextInput::make($name . '.limit')
                ->label(__('kit::admin.items_limit'))
                ->default(self::DEFAULT_LIMIT)
                ->numeric()
                ->formatStateUsing(fn ($state) => $state ?? self::DEFAULT_LIMIT),
        ]);
    }

    public function getValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $this->getDefaultValue();
        }

        $parentId = $value['parent_id'] ?? null;
        if (! $parentId) {
            return $this->getDefaultValue();
        }

        $categories = $value['categories'] ?? [];

        $query = SimplePage::query()
            ->with('tags')
            ->when(is_array($categories) && count($categories) > 0, function ($query) use ($categories) {
                // Filter by specific categories
                $query->whereIn('parent_id', $categories);
            }, function ($query) use ($parentId) {
                // Get all items from parent (including nested)
                $parent = Page::find($parentId);
                if ($parent) {
                    $descendantIds = $parent->descendants()->pluck('id')->toArray();
                    $descendantIds[] = $parentId;
                    $query->whereIn('parent_id', $descendantIds);
                }
            })
            ->when(app()->bound('page'), function ($query) {
                $query->where('id', '!=', app('page')->id);
            })
            ->limit($value['limit'] ?? self::DEFAULT_LIMIT)
            ->orderBy('published_at', 'desc')
            ->orderBy('updated_at', 'desc');

        return $query->get()->map(fn ($item): array => (new FrontPageResource($item))->toArray(request()));
    }
}

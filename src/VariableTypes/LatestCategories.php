<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use SmartCms\Kit\Http\Resources\FrontPageResource;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Models\Pages\CategoryPage;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

/**
 * @deprecated since 1.x — will be removed in 2.0. Use \SmartCms\Kit\VariableTypes\PagesList instead.
 */
class LatestCategories implements VariableTypeInterface
{
    public const int DEFAULT_LIMIT = 3;

    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'latest_categories';
    }

    public function getDefaultValue(): mixed
    {
        return CategoryPage::query()->with('tags')->limit(self::DEFAULT_LIMIT)->get()->map(fn ($item): array => (new FrontPageResource($item))->toArray(request()));
    }

    public function getSchema(string $name, ?string $language = null): Field | Component
    {
        $lang = $language ?? main_lang();

        return Group::make([
            Select::make($name . '.parent_id')
                ->label(__('kit::admin.parent_category'))
                ->options(Page::query()->where('type', 'category')->get()->mapWithKeys(fn (Page $page) => [$page->id => $page->getTranslation('name', $lang)]))
                ->required()
                ->helperText(__('kit::admin.select_parent_for_categories')),
            TextInput::make($name . '.limit')
                ->label(__('kit::admin.categories_limit'))
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

        return CategoryPage::query()
            ->with('tags')
            ->where('parent_id', $parentId)
            ->limit($value['limit'] ?? self::DEFAULT_LIMIT)
            ->orderBy('published_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn ($item): array => (new FrontPageResource($item))->toArray(request()));
    }
}

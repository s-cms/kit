<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use SmartCms\Kit\Models\Front\FrontPage;
use SmartCms\Kit\Models\Page;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class RandomItems implements VariableTypeInterface
{
    public const int DEFAULT_LIMIT = 3;

    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'random_items';
    }

    public function getDefaultValue(): mixed
    {
        return FrontPage::query()->limit(self::DEFAULT_LIMIT)->get();
    }

    public function getSchema(string $name): Field | Component
    {
        return Group::make([
            Select::make($name . '.parent_id')
                ->label(__('kit::admin.parent_category'))
                ->options(Page::query()->whereIn('type', ['category', 'division'])->pluck('name', 'id'))
                ->required()
                ->live()
                ->helperText(__('kit::admin.select_parent_for_items')),
            Select::make($name . '.categories')
                ->label(__('kit::admin.filter_by_categories'))
                ->options(fn (Get $get) => Page::query()
                    ->where('type', 'category')
                    ->where('parent_id', $get($name . '.parent_id') ?? 0)
                    ->pluck('name', 'id'))
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

        $query = FrontPage::query()
            ->where('type', '!=', 'category')
            ->when(is_array($categories) && count($categories) > 0, function ($query) use ($categories) {
                $query->whereIn('parent_id', $categories);
            }, function ($query) use ($parentId) {
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
            ->inRandomOrder();

        return $query->get();
    }
}

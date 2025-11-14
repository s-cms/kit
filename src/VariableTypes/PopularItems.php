<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use SmartCms\Kit\Http\Resources\SimplePageResource;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Models\Pages\SimplePage;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class PopularItems implements VariableTypeInterface
{
    public const int DEFAULT_LIMIT = 3;

    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'popular_items';
    }

    public function getDefaultValue(): mixed
    {
        return SimplePage::query()->limit(self::DEFAULT_LIMIT)->get()->map(fn ($item): array => (new SimplePageResource($item))->toArray(request()));
    }

    public function getSchema(string $name): Field | Component
    {
        return Group::make([
            Select::make($name . '.categories')
                ->label(__('kit::admin.filter_by_categories'))
                ->options(Page::query()
                    ->where('type', 'category')
                    ->pluck('name', 'id'))
                ->live()
                ->multiple()
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

        $categories = $value['categories'] ?? [];

        $query = SimplePage::query()
            ->whereIn('parent_id', $categories)
            ->where('type', 'page')
            ->when(app()->bound('page'), function ($query) {
                $query->where('id', '!=', app('page')->id);
            })
            ->limit($value['limit'] ?? self::DEFAULT_LIMIT)
            ->orderBy('views', 'desc');

        return $query->get()->map(fn ($item): array => (new SimplePageResource($item))->toArray(request()));
    }
}

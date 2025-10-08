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
        return FrontPage::query()->limit(self::DEFAULT_LIMIT)->get();
    }

    public function getSchema(string $name): Field | Component
    {
        return Group::make([
            Select::make($name . '.root_id')->options(Page::query()->where('parent_id', null)->whereJsonContains('settings->is_categories', true)->where('is_root', true)->pluck('name', 'id'))->required()->live(),
            Select::make($name . '.categories')->label(__('kit::admin.categories'))->options(fn(Get $get) => Page::query()->where('is_root', false)->where('parent_id', $get($name . '.root_id') ?? 0)->pluck('name', 'id'))->live()->multiple()->visible(fn(Get $get) => Page::query()->find($get($name . '.root_id'))?->settings['is_categories'] ?? false)->helperText(__('kit::admin.categories_helper_text')),
            TextInput::make($name . '.limit')->default(self::DEFAULT_LIMIT)->numeric()->formatStateUsing(fn($state) => $state ?? self::DEFAULT_LIMIT),
        ]);
    }

    public function getValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $this->getDefaultValue();
        }
        $root = Page::find($value['root_id'] ?? 0);
        if (! $root) {
            return $this->getDefaultValue();
        }
        $categories = $value['categories'] ?? [];
        $isCategories = $root->settings['is_categories'] ?? false;
        return FrontPage::query()->where('root_id', $value['root_id'] ?? 0)
            ->when($isCategories, function ($query) use ($root) {
                $query->where('parent_id', '!=', $root->id);
            })
            ->when(! $isCategories, function ($query) use ($root) {
                $query->where('parent_id', $root->id);
            })
            ->when($isCategories && is_array($categories) && count($categories) > 0, function ($query) use ($categories) {
                $query->whereIn('parent_id', $categories);
            })
            ->when(app()->bound('page'), function ($query) {
                $query->where('id', '!=', app('page')->id);
            })
            ->limit($value['limit'] ?? 3)->orderBy('published_at', 'desc')->orderBy('updated_at', 'desc')->get();
    }
}

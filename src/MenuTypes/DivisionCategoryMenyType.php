<?php

namespace SmartCms\Kit\MenuTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;

class DivisionCategoryMenyType extends PageMenuType
{
    public function getType(): string
    {
        return 'subcategory';
    }

    public function getLabel(): string
    {
        return __('kit::admin.subcategory');
    }

    public function getSchema(): Field
    {
        // Get all categories that have category children
        $parentCategories = Page::query()
            ->where('status', PageStatus::Published->value)
            ->whereIn('type', ['category', 'division'])
            ->whereHas('children', function ($query) {
                $query->where('type', 'category');
            })
            ->get();

        return Select::make('url')
            ->options($parentCategories->mapWithKeys(fn ($parent) => [
                $parent->name => Page::query()
                    ->where('status', PageStatus::Published->value)
                    ->where('type', 'category')
                    ->where('parent_id', $parent->id)
                    ->pluck('name', 'id')
                    ->toArray(),
            ]))
            ->live()
            ->afterStateUpdated(function (string $state, Set $set): void {
                if ($state !== '' && $state !== '0') {
                    $page = Page::find($state);
                    if ($page) {
                        $set('title', $page->name);
                    }
                }
            });
    }
}

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

    public function getSchema(?string $language = null): Field
    {
        $lang = $language ?? main_lang();

        $parentCategories = Page::query()
            ->where('status', PageStatus::Published->value)
            ->where('type', 'category')
            ->whereHas('children', function ($query) {
                $query->where('type', 'category');
            })
            ->get();

        return Select::make('url')
            ->options($parentCategories->mapWithKeys(fn ($parent) => [
                $parent->getTranslation('name', $lang) => Page::query()
                    ->where('status', PageStatus::Published->value)
                    ->where('type', 'category')
                    ->where('parent_id', $parent->id)
                    ->get()
                    ->mapWithKeys(fn (Page $page) => [$page->id => $page->getTranslation('name', $lang)])
                    ->toArray(),
            ]))
            ->live()
            ->afterStateUpdated(function (string $state, Set $set) use ($lang): void {
                if ($state !== '' && $state !== '0') {
                    $page = Page::find($state);
                    if ($page) {
                        $set('title', $page->getTranslation('name', $lang));
                    }
                }
            });
    }
}

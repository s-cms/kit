<?php

namespace SmartCms\Kit\MenuTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;

class DivisionMenuType extends PageMenuType
{
    public function getType(): string
    {
        return 'category';
    }

    public function getLabel(): string
    {
        return __('kit::admin.category');
    }

    public function getSchema(?string $language = null): Field
    {
        $lang = $language ?? main_lang();

        return Select::make('url')
            ->options($this->buildCategoriesTree($lang))
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

    protected function buildCategoriesTree(string $lang): array
    {
        $categories = Page::query()
            ->where('status', PageStatus::Published->value)
            ->where('type', 'category')
            ->orderBy('sorting')
            ->get()
            ->groupBy(fn (Page $page) => $page->parent_id ?? 0);

        $options = [];
        $this->appendCategoryBranch($categories, 0, 0, $lang, $options);

        return $options;
    }

    protected function appendCategoryBranch($groups, $parentId, int $depth, string $lang, array &$options): void
    {
        $children = $groups->get($parentId, collect());

        foreach ($children as $page) {
            $indent = str_repeat('— ', $depth);
            $options[$page->id] = $indent . $page->getTranslation('name', $lang);
            $this->appendCategoryBranch($groups, $page->id, $depth + 1, $lang, $options);
        }
    }
}

<?php

namespace SmartCms\Kit\Actions\Admin;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Lorisleiva\Actions\Concerns\AsAction;
use SmartCms\Kit\Admin\Resources\Pages\Pages\EditMenuSection;
use SmartCms\Kit\Admin\Resources\Pages\Pages\ListCategories;
use SmartCms\Kit\Admin\Resources\Pages\Pages\ListItems;
use SmartCms\Kit\Admin\Resources\Pages\Pages\ListPages;
use SmartCms\Kit\Models\Page;

class GetPageNavigation
{
    use AsAction;

    public function handle(): array
    {
        $pages = Page::query()->where('is_root', true)->get();
        $navigation = [
            NavigationItem::make('pages')->label(__('kit::admin.pages'))->url(ListPages::getUrl())->icon('heroicon-o-newspaper')->isActiveWhen(fn () => request()->routeIs(ListPages::getRouteName())),
        ];
        foreach ($pages as $page) {
            $navigation[] = NavigationGroup::make($page->getTranslation('name', main_lang()))->collapsed()->label($page->getTranslation('name', main_lang()))->items(array_filter([
                isset($page->settings['is_categories']) && $page->settings['is_categories'] ?
                    NavigationItem::make('categories')->label(__('kit::admin.categories'))->url(ListCategories::getUrl(['record' => $page->id]))->icon('heroicon-o-newspaper')->isActiveWhen(fn (): bool => request()->routeIs(ListCategories::getRouteName()) && request()->route('record') == $page->id) : null,
                NavigationItem::make('items')->label(__('kit::admin.items'))->url(ListItems::getUrl(['record' => $page->id]))->icon('heroicon-o-newspaper')->isActiveWhen(fn (): bool => request()->routeIs(ListItems::getRouteName()) && request()->route('record') == $page->id),
                NavigationItem::make('settings')->label(__('kit::admin.settings'))->url(EditMenuSection::getUrl(['record' => $page->id]))->icon('heroicon-o-cog')->isActiveWhen(fn (): bool => request()->routeIs(EditMenuSection::getRouteName()) && request()->route('record') == $page->id),
            ]));
        }

        return $navigation;
    }
}

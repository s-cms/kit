<?php

namespace SmartCms\Kit\MenuTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;
use SmartCms\Menu\MenuTypeInterface;

class PageMenuType implements MenuTypeInterface
{
    public function getType(): string
    {
        return 'page';
    }

    public function getLabel(): string
    {
        return __('kit::admin.page');
    }

    public function getSchema(?string $language = null): Field
    {
        $lang = $language ?? main_lang();

        return Select::make('url')
            ->options(
                Page::query()
                    ->where('status', PageStatus::Published->value)
                    ->where('type', 'page')
                    ->where('parent_id', null)
                    ->where('is_root', false)
                    ->get()
                    ->mapWithKeys(fn (Page $page) => [$page->id => $page->getTranslation('name', $lang)])
                    ->toArray(),
            )
            ->live()
            ->afterStateUpdated(function (string $state, $livewire, Component $component) use ($lang): void {
                $statePath = $component->getStatePath();
                $statePath = explode('.', $statePath);
                array_shift($statePath);
                $statePath[array_key_last($statePath)] = 'title';
                $statePath = implode('.', $statePath);
                if ($state !== '' && $state !== '0') {
                    $page = Page::find($state);
                    if ($page && property_exists($livewire, 'data')) {
                        $livewire->data = data_set($livewire->data, $statePath, $page->getTranslation('name', $lang));
                    }
                }
            });
    }

    public function getLinkFromItem(mixed $item): string | array
    {
        return Page::find($item['url'] ?? 0)?->route() ?? url('/');
    }
}

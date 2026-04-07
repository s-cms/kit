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
            ->options(
                Page::query()
                    ->where('status', PageStatus::Published->value)
                    ->where('type', 'category')
                    ->get()
                    ->mapWithKeys(fn (Page $page) => [$page->id => $page->getTranslation('name', $lang)])
                    ->toArray(),
            )
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

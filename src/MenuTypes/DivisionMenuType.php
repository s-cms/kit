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

    public function getSchema(): Field
    {
        return Select::make('url')
            ->options(Page::query()
                ->where('status', PageStatus::Published->value)
                ->whereIn('type', ['category', 'division']) // Support both new and old types
                ->pluck('name', 'id'))
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

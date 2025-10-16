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
        return 'division_category';
    }

    public function getLabel(): string
    {
        return __('kit::admin.division_category');
    }

    public function getSchema(): Field
    {
        $roots = Page::query()->where('status', PageStatus::Published->value)->whereJsonContains('settings->is_categories', true)->where('is_root', true)->get();
        return Select::make('url')
            ->options($roots->mapWithKeys(fn($root) => [
                $root->name => Page::query()->where('status', PageStatus::Published->value)->where('parent_id', $root->id)->pluck('name', 'id')->toArray()
            ]))->live()->afterStateUpdated(function (string $state, Set $set): void {
                if ($state !== '' && $state !== '0') {
                    $page = Page::find($state);
                    if ($page) {
                        $set('title', $page->name);
                    }
                }
            });
    }
}

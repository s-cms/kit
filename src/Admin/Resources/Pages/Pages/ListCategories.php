<?php

namespace SmartCms\Kit\Admin\Resources\Pages\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use SmartCms\Kit\Actions\Admin\GetPageNavigation;
use SmartCms\Kit\Admin\Forms\PageNameField;
use SmartCms\Kit\Admin\Forms\PageSlugField;
use SmartCms\Kit\Admin\Resources\Pages\PageResource;
use SmartCms\Kit\Models\Page;

class ListCategories extends ListRecords
{
    protected static string $resource = PageResource::class;

    public Page $rootPage;

    public function mount(): void
    {
        $this->rootPage = Page::find(request('record'));
        parent::mount();
    }

    public function getTitle(): string | Htmlable
    {
        return $this->rootPage->name . ' ' . __('kit::admin.categories');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('_create')
                ->label(__('filament-actions::create.single.label', ['label' => __('kit::admin.category')]))
                ->schema([
                    PageNameField::make(),
                    PageSlugField::make(),
                ])->action(function (array $data): void {
                    Page::query()->create([
                        'name' => $data['name'],
                        'slug' => $data['slug'],
                        'parent_id' => $this->rootPage->id,
                        'root_id' => $this->rootPage->id,
                    ]);
                }),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getSubNavigation(): array
    {
        return array_merge(parent::getSubNavigation(), GetPageNavigation::run());
    }

    public function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query) => $query->where('parent_id', $this->rootPage->id));
    }
}

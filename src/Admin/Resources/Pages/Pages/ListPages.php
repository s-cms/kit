<?php

namespace SmartCms\Kit\Admin\Resources\Pages\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use SmartCms\Kit\Actions\Admin\GetPageNavigation;
use SmartCms\Kit\Admin\Forms\PageNameField;
use SmartCms\Kit\Admin\Forms\PageSlugField;
use SmartCms\Kit\Admin\Resources\Pages\PageResource;
use SmartCms\Kit\Models\Page;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Single create action for all page types
            Action::make('_create')
                ->label(__('filament-actions::create.single.label', ['label' => PageResource::getModelLabel()]))
                ->modalWidth(Width::ExtraLarge)
                ->modal()
                ->color('primary')
                ->schema([
                    PageNameField::make(),
                    PageSlugField::make(),
                    Select::make('type')
                        ->label(__('kit::admin.page_type'))
                        ->options([
                            'page' => __('kit::admin.type_page'),
                            'category' => __('kit::admin.type_category'),
                        ])
                        ->default('page')
                        ->required(),
                    Select::make('parent_id')
                        ->label(__('kit::admin.parent_page'))
                        ->options(function () {
                            return Page::query()
                                ->whereIn('type', ['category', 'division'])
                                ->where('depth', '<', config('kit.max_page_depth', 5) - 1)
                                ->orderBy('slug')
                                ->get()
                                ->mapWithKeys(function (Page $page) {
                                    $indent = str_repeat('— ', $page->depth);
                                    $label = $indent . $page->name . ' (' . $page->type . ')';
                                    return [$page->id => $label];
                                })
                                ->toArray();
                        })
                        ->searchable()
                        ->placeholder(__('kit::admin.no_parent')),
                ])
                ->action(function (array $data): void {
                    Page::query()->create($data);
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
        return $table
            // Show all pages - no more filtering by is_root or parent_id
            ->filters([
                SelectFilter::make('type')
                    ->label(__('kit::admin.type'))
                    ->options([
                        'page' => __('kit::admin.type_page'),
                        'category' => __('kit::admin.type_category'),
                    ])
                    ->multiple(),
                SelectFilter::make('parent_id')
                    ->label(__('kit::admin.parent_page'))
                    ->options(function () {
                        return Page::query()
                            ->whereIn('type', ['category', 'division'])
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable(),
                SelectFilter::make('depth')
                    ->label(__('kit::admin.depth'))
                    ->options([
                        0 => __('kit::admin.root_level'),
                        1 => __('kit::admin.level_1'),
                        2 => __('kit::admin.level_2'),
                        3 => __('kit::admin.level_3'),
                        4 => __('kit::admin.level_4'),
                    ]),
            ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.pages');
    }

    public function getHeading(): string | Htmlable
    {
        return __('kit::admin.pages');
    }
}

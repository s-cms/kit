<?php

namespace SmartCms\Kit\Admin\Resources\Pages\Tables;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SpatieTagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use SmartCms\Kit\Admin\Forms\PageNameField;
use SmartCms\Kit\Admin\Forms\PageSlugField;
use SmartCms\Kit\Admin\Resources\Pages\PageResource;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;
use SmartCms\Support\Admin\Components\Actions\ViewRecord;
use SmartCms\Support\Admin\Components\Filters\StatusFilter;
use SmartCms\Support\Admin\Components\Tables\CreatedAtColumn;
use SmartCms\Support\Admin\Components\Tables\NameColumn;
use SmartCms\Support\Admin\Components\Tables\UpdatedAtColumn;
use SmartCms\Support\Admin\Components\Tables\ViewsColumn;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('type', ['page', 'category']))
            ->columns([
                NameColumn::make()
                    ->getStateUsing(fn(Page $record) => $record->getTranslation('name', main_lang()))
                    ->description(fn(Page $record): string => Str::limit($record->slug, 30)),
                TextColumn::make('type')
                    ->label(__('kit::admin.type'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'category' => 'success',
                        'page' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'category' => __('kit::admin.type_category'),
                        'page' => __('kit::admin.type_page'),
                        default => ucfirst($state),
                    }),
                TextColumn::make('parent.name')
                    ->label(__('kit::admin.parent'))
                    ->formatStateUsing(fn($state, Page $record) => $record->parent ? $record->parent->getTranslation('name', main_lang()) : '-')
                    ->toggleable(),
                SpatieTagsColumn::make('tags')
                    ->label(__('kit::admin.tags'))
                    ->limitList(3),
                TextColumn::make('depth')
                    ->label(__('kit::admin.depth'))
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                ImageColumn::make('image.source')
                    ->square()
                    ->getStateUsing(fn($record): string | array => validateImage(ltrim($record?->image['source'] ?? '', '/')))
                    ->defaultImageUrl(no_image()['source'] ?? '')
                    ->default(no_image()['source'])
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(mixed $state) => PageStatus::tryFrom($state)?->getColor())
                    ->formatStateUsing(fn(mixed $state) => PageStatus::tryFrom($state)?->getLabel()),
                ViewsColumn::make()->toggleable(),
                UpdatedAtColumn::make()->toggleable(),
                CreatedAtColumn::make()->toggleable(),
                // Add augmented columns from augmentations
                ...Page::getAugmentedColumns(),
            ])
            ->defaultSort('depth', 'asc')
            ->reorderable('sorting')
            ->filters([
                StatusFilter::make(),
                SelectFilter::make('tags')
                    ->label(__('kit::admin.tags'))
                    ->multiple()
                    ->relationship('tags', 'name->' . main_lang())
                    ->preload()
                    ->searchable(),
                SelectFilter::make('type')
                    ->label(__('kit::admin.type'))
                    ->options([
                        'page' => __('kit::admin.type_page'),
                        'category' => __('kit::admin.type_category'),
                    ]),
                // Add augmented filters from augmentations
                ...Page::getAugmentedFilters(),
            ])
            ->recordActions([
                // DeleteAction::make()->iconButton()->hidden(fn($record) => $record->is_system),
                // EditAction::make()->iconButton(),
                Action::make('clone')
                    ->label(__('kit::admin.clone_page'))
                    ->iconButton()
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->schema([
                        PageNameField::make()
                            ->default(fn(Page $record) => $record->name . ' (Copy)'),
                        PageSlugField::make()
                            ->default(fn(Page $record) => $record->slug . '-copy'),
                        Select::make('parent_id')
                            ->label(__('kit::admin.parent_page'))
                            ->options(function (Page $record) {
                                $maxDepth = config('kit.max_page_depth', 5);

                                return Page::query()
                                    ->where('id', '!=', $record->id)
                                    ->where('type', 'category')
                                    ->where('depth', '<', $maxDepth - 1)
                                    ->orderBy('slug')
                                    ->get()
                                    ->mapWithKeys(function (Page $page) use ($record) {
                                        // Exclude descendants
                                        if ($record->exists) {
                                            $descendantIds = $record->descendants()->pluck('id')->toArray();
                                            if (in_array($page->id, $descendantIds)) {
                                                return [];
                                            }
                                        }
                                        $indent = str_repeat('— ', $page->depth);
                                        $label = $indent . $page->name;

                                        return [$page->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->default(fn(Page $record) => $record->parent_id)
                            ->searchable()
                            ->placeholder(__('kit::admin.no_parent')),
                    ])
                    ->action(function (Page $record, array $data): void {
                        // Clone the page
                        $clone = $record->replicate(['views', 'published_at']);
                        $clone->name = $data['name'];
                        $clone->slug = $data['slug'];
                        $clone->parent_id = $data['parent_id'] ?? null;
                        $clone->status = PageStatus::Draft;
                        $clone->published_at = null;
                        $clone->views = 0;

                        // Recalculate depth based on new parent
                        if ($clone->parent_id) {
                            $parent = Page::find($clone->parent_id);
                            $clone->depth = $parent ? $parent->depth + 1 : 0;
                        } else {
                            $clone->depth = 0;
                        }

                        $clone->save();

                        // Clone blocks relationship
                        if ($record->blocks->count() > 0) {
                            foreach ($record->blocks as $block) {
                                $clone->blocks()->attach($block->id, [
                                    'status' => $block->pivot->status,
                                    'sorting' => $block->pivot->sorting,
                                    'show_from' => $block->pivot->show_from,
                                    'show_until' => $block->pivot->show_until,
                                ]);
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title(__('kit::admin.page_cloned_successfully'))
                            ->send();

                        redirect()->to(PageResource::getUrl('edit', ['record' => $clone]));
                    }),
                ViewRecord::make()->iconButton(),
                // Add augmented record actions from augmentations
                ...Page::getAugmentedRecordActions(),
            ])
            ->headerActions([
                // Add augmented header actions from augmentations
                ...Page::getAugmentedHeaderActions(),
            ])
            ->toolbarActions([
                // Add augmented toolbar actions from augmentations
                ...Page::getAugmentedToolbarActions(),
            ]);
    }
}

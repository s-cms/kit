<?php

namespace SmartCms\Kit\Admin\Resources\Pages\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\SpatieTagsColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use SmartCms\Kit\Admin\Forms\PageNameField;
use SmartCms\Kit\Admin\Forms\PageSlugField;
use SmartCms\Kit\Admin\Resources\Pages\PageResource;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        $ownerRecord = $this->getOwnerRecord();

        return $table
            ->heading(__('kit::admin.child_pages'))
            ->recordAction('edit')
            ->description(__('kit::admin.child_pages_description'))
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('sorting'))
            ->reorderable('sorting')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('kit::admin.name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (Page $record): string => $record->slug),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('kit::admin.type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'category' => 'success',
                        'page' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('kit::admin.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => PageStatus::tryFrom($state)?->getLabel()),
                SpatieTagsColumn::make('tags')
                    ->label(__('kit::admin.tags'))
                    ->limitList(3),

                Tables\Columns\TextColumn::make('children_count')
                    ->label(__('kit::admin.children_count'))
                    ->counts('children')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('published_at')
                    ->label(__('kit::admin.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('kit::admin.type'))
                    ->options([
                        'page' => __('kit::admin.type_page'),
                        'category' => __('kit::admin.type_category'),
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label(__('kit::admin.status'))
                    ->options(fn () => [
                        'published' => __('kit::admin.published'),
                        'draft' => __('kit::admin.draft'),
                        'scheduled' => __('kit::admin.scheduled'),
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->schema([
                        PageNameField::make(),
                        PageSlugField::make(),
                        Forms\Components\Select::make('type')
                            ->label(__('kit::admin.page_type'))
                            ->options([
                                'page' => __('kit::admin.type_page'),
                                'category' => __('kit::admin.type_category'),
                            ])
                            ->default('page')
                            ->required(),
                    ])
                    ->mutateDataUsing(function (array $data) use ($ownerRecord): array {
                        $data['parent_id'] = $ownerRecord->id;
                        $data['depth'] = $ownerRecord->depth + 1;

                        return $data;
                    })
                    ->hidden(function () use ($ownerRecord): bool {
                        // Hide if owner can't have children
                        if (! $ownerRecord->canHaveChildren()) {
                            return true;
                        }

                        // Hide if depth limit reached
                        $maxDepth = config('kit.max_page_depth', 5);

                        return $ownerRecord->depth >= $maxDepth - 1;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (Page $record): string => route('filament.admin.resources.pages.edit', ['record' => $record]))
                    ->icon('heroicon-o-pencil-square'),

                Action::make('clone')
                    ->label(__('kit::admin.clone_page'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->schema([
                        PageNameField::make()
                            ->default(fn (Page $record) => $record->name . ' (Copy)'),
                        PageSlugField::make()
                            ->default(fn (Page $record) => $record->slug . '-copy'),
                    ])
                    ->action(function (Page $record, array $data) use ($ownerRecord): void {
                        // Clone the page
                        $clone = $record->replicate(['views', 'published_at']);
                        $clone->name = $data['name'];
                        $clone->slug = $data['slug'];
                        $clone->parent_id = $ownerRecord->id; // Keep same parent
                        $clone->status = PageStatus::Draft;
                        $clone->published_at = null;
                        $clone->views = 0;
                        $clone->depth = $ownerRecord->depth + 1;

                        $clone->save();

                        // Clone blocks relationship
                        foreach ($record->blocks as $block) {
                            $clone->blocks()->attach($block->id, [
                                'status' => $block->pivot->status,
                                'sorting' => $block->pivot->sorting,
                                'show_from' => $block->pivot->show_from,
                                'show_until' => $block->pivot->show_until,
                            ]);
                        }

                        // Clone template relationship
                        foreach ($record->template as $template) {
                            $clone->template()->create([
                                'section_id' => $template->section_id,
                                'sorting' => $template->sorting,
                            ]);
                        }

                        Notification::make()
                            ->success()
                            ->title(__('kit::admin.page_cloned_successfully'))
                            ->send();

                        redirect()->to(PageResource::getUrl('edit', ['record' => $clone]));
                    }),

                Action::make('_view')
                    ->label(__('kit::admin.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Page $record): string => $record->route())
                    ->openUrlInNewTab(),

                DeleteAction::make()
                    ->hidden(fn (Page $record): bool => $record->children()->count() > 0),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, $records) {
                            // Check if any record has children
                            foreach ($records as $record) {
                                if ($record->children()->count() > 0) {
                                    Notification::make()
                                        ->title(__('kit::admin.cannot_delete_pages_with_children'))
                                        ->danger()
                                        ->send();
                                    $action->halt();
                                }
                            }
                        }),
                ]),
            ])
            ->emptyStateHeading(__('kit::admin.no_children'))
            ->emptyStateDescription(__('kit::admin.no_children_description'))
            ->emptyStateIcon('heroicon-o-document-plus');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('kit::admin.children');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        // Only show for pages that can have children
        return $ownerRecord->canHaveChildren();
    }
}

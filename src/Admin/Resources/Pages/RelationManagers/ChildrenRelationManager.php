<?php

namespace SmartCms\Kit\Admin\Resources\Pages\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use SmartCms\Kit\Admin\Forms\PageNameField;
use SmartCms\Kit\Admin\Forms\PageSlugField;
use SmartCms\Kit\Models\Page;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        $ownerRecord = $this->getOwnerRecord();

        return $table
            ->heading(__('kit::admin.child_pages'))
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
                    ->formatStateUsing(fn ($state) => $state?->getLabel()),

                Tables\Columns\TextColumn::make('depth')
                    ->label(__('kit::admin.depth'))
                    ->badge()
                    ->color('gray'),

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
                    ->mutateFormDataUsing(function (array $data) use ($ownerRecord): array {
                        $data['parent_id'] = $ownerRecord->id;
                        $data['depth'] = $ownerRecord->depth + 1;

                        return $data;
                    })
                    ->disabled(function () use ($ownerRecord): bool {
                        // Check if owner can have children
                        if (! $ownerRecord->canHaveChildren()) {
                            return true;
                        }

                        // Check depth limit
                        $maxDepth = config('kit.max_page_depth', 5);

                        return $ownerRecord->depth >= $maxDepth - 1;
                    })
                    ->disabledTooltip(function () use ($ownerRecord): ?string {
                        if (! $ownerRecord->canHaveChildren()) {
                            return __('kit::admin.parent_cannot_have_children', ['type' => $ownerRecord->type]);
                        }

                        $maxDepth = config('kit.max_page_depth', 5);
                        if ($ownerRecord->depth >= $maxDepth - 1) {
                            return __('kit::admin.max_depth_reached', ['max' => $maxDepth]);
                        }

                        return null;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (Page $record): string => route('filament.admin.resources.pages.edit', ['record' => $record]))
                    ->icon('heroicon-o-pencil-square'),

                Tables\Actions\Action::make('view')
                    ->label(__('kit::admin.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Page $record): string => $record->route())
                    ->openUrlInNewTab(),

                DeleteAction::make()
                    ->disabled(fn (Page $record): bool => $record->children()->count() > 0)
                    ->disabledTooltip(__('kit::admin.cannot_delete_page_with_children')),
            ])
            ->toolbarActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, $records) {
                            // Check if any record has children
                            foreach ($records as $record) {
                                if ($record->children()->count() > 0) {
                                    $action->failureNotificationTitle = __('kit::admin.cannot_delete_pages_with_children');
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

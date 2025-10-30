<?php

namespace SmartCms\Kit\Admin\Resources\Pages\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Services\Block\BlockService;
use SmartCms\Lang\Models\Language;
use SmartCms\Support\Admin\Components\Layout\LeftGrid;
use SmartCms\Support\Admin\Components\Layout\RightGrid;

class BlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'blocks';

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('block_id')
                    ->label(__('Block'))
                    ->options(fn () => Block::query()->pluck('title', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Toggle::make('status')
                    ->label(__('Active'))
                    ->default(true)
                    ->required(),

                Forms\Components\DateTimePicker::make('show_from')
                    ->label(__('Show From'))
                    ->nullable(),

                Forms\Components\DateTimePicker::make('show_until')
                    ->label(__('Show Until'))
                    ->nullable(),

                Forms\Components\TextInput::make('sorting')
                    ->label(__('Sorting'))
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('sorting')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Block'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->searchable(),

                Tables\Columns\ToggleColumn::make('status')
                    ->label(__('Active')),

                Tables\Columns\TextColumn::make('sorting')
                    ->label(__('Sorting'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('show_from')
                    ->label(__('Show From'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('show_until')
                    ->label(__('Show Until'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('sorting', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('blockables.status')
                    ->label(__('Active'))
                    ->boolean()
                    ->trueLabel(__('Active only'))
                    ->falseLabel(__('Inactive only'))
                    ->native(false),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return Block::query()
                                    ->where('title', 'like', "%{$search}%")
                                    ->orWhere('type', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('title', 'id');
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => Block::find($value)?->title),

                        Forms\Components\Toggle::make('status')
                            ->label(__('Active'))
                            ->default(true),

                        Forms\Components\DateTimePicker::make('show_from')
                            ->label(__('Show From'))
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('show_until')
                            ->label(__('Show Until'))
                            ->nullable(),

                        Forms\Components\TextInput::make('sorting')
                            ->label(__('Sorting'))
                            ->numeric()
                            ->default(0),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth('5xl')
                    ->fillForm(function (Model $record): array {
                        $pivot = $record->pivot;
                        $block = Block::find($record->id);

                        return [
                            'data' => $block->getTranslations('data') ?? [],
                            'status' => $pivot->status,
                            'show_from' => $pivot->show_from,
                            'show_until' => $pivot->show_until,
                            'sorting' => $pivot->sorting,
                        ];
                    })
                    ->schema(function (Model $record): array {
                        $service = app(BlockService::class);
                        $block = Block::find($record->id);

                        return [
                            Grid::make()->gridContainer()
                                ->columns([
                                    '@md' => 3,
                                    '@xl' => 4,
                                ])
                                ->columnSpanFull()
                                ->schema([
                                    LeftGrid::make()->schema([
                                        Tabs::make('Block Data')->schema(
                                            app('lang')->adminLanguages()->map(function (Language $lang) use ($service, $block) {
                                                return Tab::make($lang->name)->schema(
                                                    $service->getBlockSchema($block->type, $lang->slug)
                                                );
                                            })->toArray()
                                        ),
                                    ]),
                                    RightGrid::make()->schema([
                                        Section::make(__('Attachment Settings'))
                                            ->schema([
                                                Forms\Components\Toggle::make('status')
                                                    ->label(__('Active'))
                                                    ->default(true)
                                                    ->required(),
                                                Forms\Components\DateTimePicker::make('show_from')
                                                    ->label(__('Show From'))
                                                    ->nullable(),
                                                Forms\Components\DateTimePicker::make('show_until')
                                                    ->label(__('Show Until'))
                                                    ->nullable(),
                                                Forms\Components\TextInput::make('sorting')
                                                    ->label(__('Sorting'))
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required(),
                                            ]),
                                    ]),
                                ]),
                        ];
                    })
                    ->extraModalFooterActions([
                        Action::make('copyAndSave')
                            ->label(__('Copy and Save'))
                            ->cancelParentActions()
                            ->action(function (Model $record, Action $action, RelationManager $livewire, array $mountedActions) {
                                $block = Block::find($record->id);
                                $data = $mountedActions[0]->getRawData();
                                $blockData = $data;
                                unset($blockData['status'], $blockData['show_from'], $blockData['show_until'], $blockData['sorting'], $blockData['title'], $blockData['type'], $blockData['block_status']);

                                // Clone the block with page suffix
                                $ownerRecord = $livewire->getOwnerRecord();
                                $pageName = $ownerRecord->name ?? 'Page';

                                $newBlock = $block->replicate();
                                $newBlock->title = $block->title . ' (' . $pageName . ')';
                                $newBlock->setTranslations('data', $data['data'] ?? []);
                                $newBlock->save();
                                // Get current pivot data to preserve sorting
                                $pivotId = $record->pivot->id;
                                // Update the blockable record to point to the new block
                                DB::table('blockables')
                                    ->where('id', $pivotId)
                                    ->update([
                                        'block_id' => $newBlock->id,
                                        'show_from' => $data['show_from'] ?? null,
                                        'show_until' => $data['show_until'] ?? null,
                                        'sorting' => $data['sorting'] ?? 0,
                                        'updated_at' => now(),
                                    ]);
                                Notification::make()
                                    ->title(__('kit::admin.block_copied_successfully'))
                                    ->success()
                                    ->send();
                            })
                            ->color('success'),
                        Action::make('cancel')
                            ->label(__('Cancel'))
                            ->action(fn (EditAction $action) => $action->cancel())
                            ->color('gray'),
                    ])
                    ->modalFooterActionsAlignment('right'),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Blocks');
    }
}

// Action::make('save')
//     ->label(__('Save'))
//     ->action(function (Model $record, array $data, EditAction $action) {
//         $block = Block::find($record->id);

//         // Update block data
//         $blockData = $data;
//         unset($blockData['status'], $blockData['show_from'], $blockData['show_until'], $blockData['sorting'], $blockData['title'], $blockData['type'], $blockData['block_status']);

//         $block->update([
//             'data' => $blockData,
//         ]);

//         // Update pivot data
//         $record->pivot->update([
//             'status' => $data['status'],
//             'show_from' => $data['show_from'],
//             'show_until' => $data['show_until'],
//             'sorting' => $data['sorting'],
//         ]);

//         $action->success();
//         $action->sendSuccessNotification();
//         $action->halt();
//     })
//     ->color('primary'),

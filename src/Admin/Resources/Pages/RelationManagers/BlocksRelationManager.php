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
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Models\BlockTemplate;
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
                    ->label(__('kit::admin.block'))
                    ->options(fn () => Block::query()->pluck('title', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Toggle::make('status')
                    ->label(__('kit::admin.active'))
                    ->default(true)
                    ->required(),

                Forms\Components\DateTimePicker::make('show_from')
                    ->label(__('kit::admin.show_from'))
                    ->nullable(),

                Forms\Components\DateTimePicker::make('show_until')
                    ->label(__('kit::admin.show_until'))
                    ->nullable(),

                Forms\Components\TextInput::make('sorting')
                    ->label(__('support::admin.sorting'))
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
                    ->label(__('kit::admin.block'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('kit::admin.type'))
                    ->badge()
                    ->searchable(),

                Tables\Columns\ToggleColumn::make('status')
                    ->label(__('kit::admin.active')),

                Tables\Columns\TextColumn::make('sorting')
                    ->label(__('support::admin.sorting'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('show_from')
                    ->label(__('kit::admin.show_from'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('show_until')
                    ->label(__('kit::admin.show_until'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('sorting', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('blockables.status')
                    ->label(__('kit::admin.active'))
                    ->boolean()
                    ->trueLabel(__('kit::admin.active_only'))
                    ->falseLabel(__('kit::admin.inactive_only'))
                    ->native(false),
            ])
            ->headerActions([
                Action::make('make_template')
                    ->label(__('kit::admin.make_template'))
                    ->icon(Heroicon::BuildingOffice2)
                    ->color('info')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('kit::admin.template_name'))
                            ->required()
                            ->maxLength(255)
                            ->default(fn (RelationManager $livewire) => $livewire->getOwnerRecord()->name . ' Template')
                            ->unique(BlockTemplate::class, 'name', ignoreRecord: false)
                            ->validationMessages([
                                'unique' => __('kit::admin.template_name_already_exists'),
                            ]),
                    ])
                    ->modalHeading(__('kit::admin.make_template'))
                    ->modalDescription(__('kit::admin.make_template_description'))
                    ->action(function (array $data, RelationManager $livewire) {
                        $page = $livewire->getOwnerRecord();

                        $template = BlockTemplate::create(['name' => $data['name']]);

                        $blocksData = [];
                        foreach ($page->blocks as $block) {
                            $blocksData[$block->id] = [
                                'status' => $block->pivot->status,
                                'sorting' => $block->pivot->sorting,
                                'show_from' => $block->pivot->show_from,
                                'show_until' => $block->pivot->show_until,
                            ];
                        }

                        if (! empty($blocksData)) {
                            $template->blocks()->attach($blocksData);
                        }

                        Notification::make()
                            ->title(__('kit::admin.template_created_successfully'))
                            ->success()
                            ->send();
                    }),
                Action::make('apply_template')
                    ->label(__('kit::admin.apply_template'))
                    ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                    ->color('info')
                    ->schema([
                        Forms\Components\Select::make('template_id')
                            ->label(__('kit::admin.select_template'))
                            ->options(BlockTemplate::query()->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading(__('kit::admin.apply_template'))
                    ->modalDescription(__('kit::admin.apply_template_description'))
                    ->action(function (array $data, RelationManager $livewire) {
                        $template = BlockTemplate::find($data['template_id']);
                        if (! $template) {
                            return;
                        }

                        $page = $livewire->getOwnerRecord();
                        $template->applyToPage($page);

                        Notification::make()
                            ->title(__('kit::admin.template_applied_successfully'))
                            ->success()
                            ->send();
                    }),
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
                            ->label(__('kit::admin.active'))
                            ->default(true),

                        Forms\Components\DateTimePicker::make('show_from')
                            ->label(__('kit::admin.show_from'))
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('show_until')
                            ->label(__('kit::admin.show_until'))
                            ->nullable(),

                        Forms\Components\TextInput::make('sorting')
                            ->label(__('support::admin.sorting'))
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
                                    LeftGrid::make()->schema(
                                        self::buildBlockLanguageSchema($service, $block)
                                    ),
                                    RightGrid::make()->schema([
                                        Section::make(__('kit::admin.attachment_settings'))
                                            ->schema([
                                                Forms\Components\Toggle::make('status')
                                                    ->label(__('kit::admin.active'))
                                                    ->default(true)
                                                    ->required(),
                                                Forms\Components\DateTimePicker::make('show_from')
                                                    ->label(__('kit::admin.show_from'))
                                                    ->nullable(),
                                                Forms\Components\DateTimePicker::make('show_until')
                                                    ->label(__('kit::admin.show_until'))
                                                    ->nullable(),
                                                Forms\Components\TextInput::make('sorting')
                                                    ->label(__('support::admin.sorting'))
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
                            ->label(__('kit::admin.copy_and_save'))
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
                            ->label(__('kit::admin.cancel'))
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
        return __('kit::admin.blocks');
    }

    protected static function buildBlockLanguageSchema(BlockService $service, Block $block): array
    {
        $languages = app('lang')->adminLanguages();

        if ($languages->count() <= 1) {
            $lang = $languages->first();

            return [
                Section::make()
                    ->schema($service->getBlockSchema($block->type, $lang->slug)),
            ];
        }

        return [
            Tabs::make(__('kit::admin.block_data'))->schema(
                $languages->map(function (Language $lang) use ($service, $block) {
                    return Tab::make($lang->name)->schema(
                        $service->getBlockSchema($block->type, $lang->slug)
                    );
                })->toArray()
            ),
        ];
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

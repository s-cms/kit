<?php

namespace SmartCms\Kit\Admin\Resources\Pages\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Services\Block\BlockService;

class BlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'blocks';

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('block_id')
                    ->label(__('Block'))
                    ->options(fn() => Block::query()->pluck('title', 'id'))
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
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Block'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->searchable(),

                Tables\Columns\IconColumn::make('status')
                    ->label(__('Active'))
                    ->boolean()
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

                Tables\Columns\TextColumn::make('sorting')
                    ->label(__('Sorting'))
                    ->sortable(),
            ])
            ->defaultSort('sorting', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('status')
                    ->label(__('Active'))
                    ->boolean()
                    ->trueLabel(__('Active only'))
                    ->falseLabel(__('Inactive only'))
                    ->native(false),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->schema(fn(AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return Block::query()
                                    ->where('title', 'like', "%{$search}%")
                                    ->orWhere('type', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('title', 'id');
                            })
                            ->getOptionLabelUsing(fn($value): ?string => Block::find($value)?->title),

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
                EditAction::make()->schema([
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

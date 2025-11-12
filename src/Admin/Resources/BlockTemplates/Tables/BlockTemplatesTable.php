<?php

namespace SmartCms\Kit\Admin\Resources\BlockTemplates\Tables;

use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use SmartCms\Support\Admin\Components\Tables\CreatedAtColumn;
use SmartCms\Support\Admin\Components\Tables\UpdatedAtColumn;

class BlockTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('kit::admin.template_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('kit::admin.page_type'))
                    ->badge()
                    ->default(__('kit::admin.universal'))
                    ->searchable(),
                TextColumn::make('blocks_count')
                    ->label(__('kit::admin.blocks_count'))
                    ->counts('blocks')
                    ->sortable(),
                CreatedAtColumn::make(),
                UpdatedAtColumn::make(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

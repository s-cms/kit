<?php

namespace SmartCms\Kit\Admin\Resources\BlockTemplates\Tables;

use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                TextColumn::make('created_at')
                    ->label(__('kit::admin.created_at'))
                    ->dateTime()
                    ->since()
                    ->tooltip(function (mixed $state) {
                        return Carbon::parse($state)->format('d.m.Y H:i');
                    })
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('kit::admin.updated_at'))
                    ->dateTime()
                    ->since()
                    ->tooltip(function (mixed $state) {
                        return Carbon::parse($state)->format('d.m.Y H:i');
                    })
                    ->sortable(),
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

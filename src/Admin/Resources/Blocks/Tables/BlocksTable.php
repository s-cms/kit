<?php

namespace SmartCms\Kit\Admin\Resources\Blocks\Tables;

use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BlocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                ToggleColumn::make('status'),
                TextColumn::make('type')->badge()->searchable(),
                TextColumn::make('created_at')->dateTime()->since()->tooltip(function (mixed $state) {
                    return Carbon::parse($state)->format('d.m.Y H:i');
                }),
                TextColumn::make('updated_at')->dateTime()->since()->tooltip(function (mixed $state) {
                    return Carbon::parse($state)->format('d.m.Y H:i');
                }),
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

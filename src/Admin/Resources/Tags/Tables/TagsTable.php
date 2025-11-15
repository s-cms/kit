<?php

namespace SmartCms\Kit\Admin\Resources\Tags\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use SmartCms\Support\Admin\Components\Tables\CreatedAtColumn;
use SmartCms\Support\Admin\Components\Tables\UpdatedAtColumn;
use Spatie\Tags\Tag;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('kit::admin.tag_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('kit::admin.slug'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('kit::admin.tag_type'))
                    ->badge()
                    ->color('gray')
                    ->default('general')
                    ->sortable(),
                // TextColumn::make('taggables_count')
                //     ->label(__('kit::admin.usage_count'))
                //     ->counts('taggables')
                //     ->sortable(),
                UpdatedAtColumn::make(),
                CreatedAtColumn::make(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('kit::admin.tag_type'))
                    ->options(fn () => Tag::query()->distinct()->pluck('type', 'type')->filter()->toArray()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}

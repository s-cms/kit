<?php

namespace SmartCms\Kit\Admin\Resources\Tags\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use SmartCms\Kit\Models\Tag;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('kit::admin.tag_name'))
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn (Tag $record) => $record->getTranslation('name', main_lang())),
                TextColumn::make('slug')
                    ->label(__('kit::admin.slug'))
                    ->searchable()
                    ->getStateUsing(fn (Tag $record) => $record->getTranslation('slug', main_lang())),
                TextColumn::make('type')
                    ->label(__('kit::admin.tag_type'))
                    ->badge()
                    ->color('gray')
                    ->default('general')
                    ->sortable(),
                TextColumn::make('taggables_count')
                    ->label(__('kit::admin.usage_count'))
                    ->counts('taggables')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('kit::admin.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('kit::admin.tag_type'))
                    ->options(fn () => Tag::query()->distinct()->pluck('type', 'type')->filter()->toArray()),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}

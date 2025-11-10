<?php

namespace SmartCms\Kit\Admin\Resources\Pages\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;
use SmartCms\Support\Admin\Components\Actions\ViewRecord;
use SmartCms\Support\Admin\Components\Filters\StatusFilter;
use SmartCms\Support\Admin\Components\Tables\CreatedAtColumn;
use SmartCms\Support\Admin\Components\Tables\NameColumn;
use SmartCms\Support\Admin\Components\Tables\UpdatedAtColumn;
use SmartCms\Support\Admin\Components\Tables\ViewsColumn;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                NameColumn::make()
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', main_lang()))
                    ->description(fn (Page $record): string => $record->slug),
                TextColumn::make('type')
                    ->label(__('kit::admin.type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'category' => 'success',
                        'page' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'category' => __('kit::admin.type_category'),
                        'page' => __('kit::admin.type_page'),
                        default => ucfirst($state),
                    }),
                TextColumn::make('parent.name')
                    ->label(__('kit::admin.parent'))
                    ->formatStateUsing(fn ($state, Page $record) => $record->parent ? $record->parent->getTranslation('name', main_lang()) : '-')
                    ->toggleable(),
                TextColumn::make('depth')
                    ->label(__('kit::admin.depth'))
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                ImageColumn::make('image.source')
                    ->square()
                    ->getStateUsing(fn ($record): string | array => validateImage(ltrim($record?->image['source'] ?? '', '/')))
                    ->defaultImageUrl(no_image()['source'] ?? '')
                    ->default(no_image()['source'])
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (mixed $state) => PageStatus::tryFrom($state)?->getColor())
                    ->formatStateUsing(fn (mixed $state) => PageStatus::tryFrom($state)?->getLabel()),
                ViewsColumn::make()->toggleable(),
                UpdatedAtColumn::make()->toggleable(),
                CreatedAtColumn::make()->toggleable(),
                // Add augmented columns from augmentations
                ...Page::getAugmentedColumns(),
            ])
            ->defaultSort('depth', 'asc')
            ->reorderable('sorting')
            ->filters([
                StatusFilter::make(),
                // Add augmented filters from augmentations
                ...Page::getAugmentedFilters(),
            ])
            ->recordActions([
                // DeleteAction::make()->iconButton()->hidden(fn($record) => $record->is_system),
                // EditAction::make()->iconButton(),
                ViewRecord::make()->iconButton(),
                // Add augmented record actions from augmentations
                ...Page::getAugmentedRecordActions(),
            ])
            ->headerActions([
                // Add augmented header actions from augmentations
                ...Page::getAugmentedHeaderActions(),
            ])
            ->toolbarActions([
                // Add augmented toolbar actions from augmentations
                ...Page::getAugmentedToolbarActions(),
            ]);
    }
}

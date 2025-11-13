<?php

namespace SmartCms\Kit\Admin\Resources\Media\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use SmartCms\Support\Admin\Components\Tables\CreatedAtColumn;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('preview')
                    ->label(__('kit::admin.preview'))
                    ->getStateUsing(fn ($record) => $record->getConversionUrl('thumb'))
                    ->size(60)
                    ->square(),

                TextColumn::make('name')
                    ->label(__('kit::admin.name'))
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('file_name')
                    ->label(__('kit::admin.file_name'))
                    ->searchable()
                    ->limit(40)
                    ->toggleable(),

                TextColumn::make('dimensions')
                    ->label(__('kit::admin.dimensions'))
                    ->getStateUsing(fn ($record) => ($record->width ?? 0) . ' × ' . ($record->height ?? 0))
                    ->toggleable(),

                TextColumn::make('size')
                    ->label(__('kit::admin.file_size'))
                    ->getStateUsing(fn ($record) => format_bytes($record->size))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('mime_type')
                    ->label(__('kit::admin.type'))
                    ->badge()
                    ->color(fn ($state) => match (explode('/', $state)[0] ?? '') {
                        'image' => 'success',
                        'video' => 'info',
                        'application' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),

                CreatedAtColumn::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('mime_type')
                    ->label(__('kit::admin.file_type'))
                    ->options([
                        'image/jpeg' => 'JPEG',
                        'image/png' => 'PNG',
                        'image/gif' => 'GIF',
                        'image/webp' => 'WebP',
                        'image/svg+xml' => 'SVG',
                    ]),
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

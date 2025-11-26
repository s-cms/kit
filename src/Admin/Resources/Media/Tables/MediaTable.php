<?php

namespace SmartCms\Kit\Admin\Resources\Media\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use SmartCms\Kit\Services\ImageProcessingService;
use SmartCms\Support\Admin\Components\Tables\CreatedAtColumn;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('preview')
                    ->label(__('kit::admin.preview'))
                    ->getStateUsing(fn ($record) => $record->getUrl())
                    ->imageSize(60)
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
                Action::make('regenerate_responsive')
                    ->label(__('kit::admin.regenerate_responsive_images'))
                    ->icon('heroicon-o-arrow-path')
                    ->iconButton()
                    ->color('warning')
                    ->visible(fn ($record) => $record->isImage() && $record->mime_type !== 'image/svg+xml')
                    ->requiresConfirmation()
                    ->modalHeading(__('kit::admin.regenerate_responsive_images'))
                    ->modalDescription(__('kit::admin.regenerate_responsive_images_description'))
                    ->modalSubmitActionLabel(__('kit::admin.regenerate'))
                    ->action(function ($record) {
                        try {
                            $service = app(ImageProcessingService::class);
                            $service->processImage($record);

                            Notification::make()
                                ->success()
                                ->title(__('kit::admin.success'))
                                ->body(__('kit::admin.responsive_images_regenerated'))
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('kit::admin.error'))
                                ->body(__('kit::admin.failed_to_regenerate_responsive_images') . ': ' . $e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

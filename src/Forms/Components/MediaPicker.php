<?php

namespace SmartCms\Kit\Forms\Components;

use Filament\Actions\Action;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPicker extends SpatieMediaLibraryFileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->disk(config('kit.media.disk', 'public'))
            ->responsiveImages()
            ->conversion('original-webp')
            ->customProperties(function (TemporaryUploadedFile $file): array {
                $image = Image::load($file->getRealPath());

                return [
                    'height' => $image->getHeight(),
                    'width' => $image->getWidth(),
                ];
            })
            ->hintActions([
                // Action 1: Select from existing images
                Action::make('selectFromExisting')
                    ->icon('heroicon-o-photo')
                    ->tooltip(__('kit::admin.select_from_existing'))
                    ->modalHeading(__('kit::admin.select_from_existing'))
                    ->modalWidth('5xl')
                    ->modalSubmitActionLabel(__('kit::admin.select_image'))
                    ->form([
                        TextInput::make('library_search')
                            ->label(__('kit::admin.search'))
                            ->placeholder(__('kit::admin.search'))
                            ->live(debounce: 300)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $service = app(\SmartCms\Kit\Services\MediaLibraryService::class);
                                $media = $service->search($state ?: '', config('kit.media.collection_name'), 20);

                                $set('library_results', $media->map(function ($item) {
                                    return [
                                        'id' => $item->id,
                                        'uuid' => $item->uuid,
                                        'name' => $item->name,
                                        'thumb_url' => $item->getUrl('thumb'),
                                    ];
                                })->toArray());
                            }),

                        ViewField::make('library_grid')
                            ->view('kit::forms.components.library-grid')
                            ->afterStateHydrated(function (callable $set) {
                                $service = app(\SmartCms\Kit\Services\MediaLibraryService::class);
                                $media = $service->search('', config('kit.media.collection_name'), 20);

                                $set('library_results', $media->map(function ($item) {
                                    return [
                                        'id' => $item->id,
                                        'uuid' => $item->uuid,
                                        'name' => $item->name,
                                        'thumb_url' => $item->getUrl('thumb'),
                                    ];
                                })->toArray());
                            }),
                    ])
                    ->action(function (array $data, $livewire) {
                        if (isset($data['library_selected_uuid'])) {
                            // Set the media UUID directly in the component state
                            $livewire->dispatch('fileUploadAttached', [
                                'statePath' => $this->getStatePath(),
                                'files' => [$data['library_selected_uuid']],
                            ]);
                        }
                    }),

                // Action 2: Import by URL
                Action::make('importByUrl')
                    ->icon('heroicon-o-link')
                    ->tooltip(__('kit::admin.import_from_url'))
                    ->modalHeading(__('kit::admin.import_from_url'))
                    ->modalWidth('2xl')
                    ->modalSubmitActionLabel(__('kit::admin.fetch_image'))
                    ->form([
                        TextInput::make('url_input')
                            ->label(__('kit::admin.image_url'))
                            ->url()
                            ->required()
                            ->placeholder('https://example.com/image.jpg'),
                    ])
                    ->action(function (array $data, $livewire) {
                        if (! isset($data['url_input']) || empty($data['url_input'])) {
                            return;
                        }

                        try {
                            $service = app(\SmartCms\Kit\Services\MediaLibraryService::class);
                            $imageData = $service->storeFromUrl($data['url_input'], config('kit.media.collection_name'));

                            if (isset($imageData['media_id'])) {
                                $media = Media::find($imageData['media_id']);
                                if ($media) {
                                    // Set the media UUID directly in the component state
                                    $livewire->dispatch('fileUploadAttached', [
                                        'statePath' => $this->getStatePath(),
                                        'files' => [$media->uuid],
                                    ]);

                                    \Filament\Notifications\Notification::make()
                                        ->title(__('kit::admin.success'))
                                        ->body(__('kit::admin.image_fetched'))
                                        ->success()
                                        ->send();
                                }
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('kit::admin.error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Unsplash integration (commented out for now)
                /*
                Action::make('importFromUnsplash')
                    ->icon('heroicon-o-sparkles')
                    ->tooltip(__('kit::admin.browse_unsplash'))
                    ->visible(fn () => config('kit.unsplash.enabled', false))
                    ->modalHeading(__('kit::admin.browse_unsplash'))
                    ->modalWidth('5xl')
                    ->form([...])
                    ->action(function (array $data) {...}),
                */
            ]);
    }
}

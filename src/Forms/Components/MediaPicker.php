<?php

namespace SmartCms\Kit\Forms\Components;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Support\Facades\Http;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPicker extends SpatieMediaLibraryFileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        // Add header action for advanced media selection
        $this->hintAction(
            Action::make('selectFromLibrary')
                ->label(__('kit::admin.browse_library'))
                ->icon('heroicon-o-photo')
                ->modalHeading(__('kit::admin.select_image'))
                ->modalWidth('5xl')
                ->modalSubmitActionLabel(__('kit::admin.select_image'))
                ->form([
                    Tabs::make('MediaTabs')
                        ->tabs([
                            // URL Import Tab
                            Tab::make(__('kit::admin.import_from_url'))
                                ->schema([
                                    TextInput::make('url_input')
                                        ->label(__('kit::admin.image_url'))
                                        ->url()
                                        ->placeholder('https://example.com/image.jpg')
                                        ->suffixAction(
                                            Action::make('fetchUrl')
                                                ->label(__('kit::admin.fetch_image'))
                                                ->icon('heroicon-o-arrow-down-tray')
                                                ->action(function ($state, callable $set) {
                                                    if (! $state) {
                                                        return;
                                                    }

                                                    try {
                                                        $response = app(\SmartCms\Kit\Services\MediaLibraryService::class)
                                                            ->storeFromUrl($state, config('kit.media.collection_name'));

                                                        $set('selected_source', 'url');
                                                        $set('url_result', $response);

                                                        \Filament\Notifications\Notification::make()
                                                            ->title(__('kit::admin.success'))
                                                            ->body(__('kit::admin.image_fetched'))
                                                            ->success()
                                                            ->send();
                                                    } catch (\Exception $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title(__('kit::admin.error'))
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                })
                                        ),

                                    ViewField::make('url_result_preview')
                                        ->view('kit::forms.components.image-preview')
                                        ->visible(fn ($get) => filled($get('url_result'))),
                                ]),

                            // Unsplash Tab
                            Tab::make(__('kit::admin.browse_unsplash'))
                                ->visible(fn () => config('kit.unsplash.enabled', false))
                                ->schema([
                                    Grid::make(1)->schema([
                                        TextInput::make('unsplash_query')
                                            ->label(__('kit::admin.search_unsplash'))
                                            ->placeholder(__('kit::admin.search'))
                                            ->suffixAction(
                                                Action::make('searchUnsplash')
                                                    ->label(__('kit::admin.search'))
                                                    ->icon('heroicon-o-magnifying-glass')
                                                    ->action(function ($state, callable $set) {
                                                        if (! $state) {
                                                            return;
                                                        }

                                                        try {
                                                            $response = Http::withHeaders([
                                                                'Authorization' => 'Client-ID ' . config('kit.unsplash.access_key'),
                                                            ])->get('https://api.unsplash.com/search/photos', [
                                                                'query' => $state,
                                                                'per_page' => 12,
                                                            ]);

                                                            if ($response->successful()) {
                                                                $set('unsplash_results', $response->json('results', []));
                                                            }
                                                        } catch (\Exception $e) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title(__('kit::admin.error'))
                                                                ->body($e->getMessage())
                                                                ->danger()
                                                                ->send();
                                                        }
                                                    })
                                            ),

                                        ViewField::make('unsplash_grid')
                                            ->view('kit::forms.components.unsplash-grid')
                                            ->visible(fn ($get) => filled($get('unsplash_results'))),
                                    ]),
                                ]),

                            // Library Browser Tab
                            Tab::make(__('kit::admin.browse_library'))
                                ->schema([
                                    TextInput::make('library_search')
                                        ->label(__('kit::admin.search'))
                                        ->placeholder(__('kit::admin.search'))
                                        ->live(debounce: 300)
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $search = $state;
                                            $media = app(\SmartCms\Kit\Services\MediaLibraryService::class)
                                                ->search($search ?: '', config('kit.media.collection_name'), 20);

                                            $set('library_results', $media->map(function ($item) {
                                                return [
                                                    'id' => $item->id,
                                                    'name' => $item->name,
                                                    'thumb_url' => $item->getUrl('thumb'),
                                                    'image' => $item->toImageArray(),
                                                ];
                                            })->toArray());
                                        }),

                                    ViewField::make('library_grid')
                                        ->view('kit::forms.components.library-grid')
                                        ->afterStateHydrated(function (callable $set) {
                                            // Load initial results
                                            $media = app(\SmartCms\Kit\Services\MediaLibraryService::class)
                                                ->search('', config('kit.media.collection_name'), 20);

                                            $set('library_results', $media->map(function ($item) {
                                                return [
                                                    'id' => $item->id,
                                                    'name' => $item->name,
                                                    'thumb_url' => $item->getUrl('thumb'),
                                                    'image' => $item->toImageArray(),
                                                ];
                                            })->toArray());
                                        }),
                                ]),
                        ])
                        ->contained(false),
                ])
                ->action(function (array $data, callable $get, callable $set): void {
                    // Determine which source was used and set the appropriate data
                    $imageData = null;

                    if (isset($data['selected_source'])) {
                        if ($data['selected_source'] === 'url' && isset($data['url_result'])) {
                            $imageData = $data['url_result'];
                        } elseif ($data['selected_source'] === 'unsplash' && isset($data['unsplash_selected'])) {
                            $imageData = $data['unsplash_selected'];
                        } elseif ($data['selected_source'] === 'library' && isset($data['library_selected'])) {
                            $imageData = $data['library_selected'];
                        }
                    }

                    if ($imageData && isset($imageData['media_id'])) {
                        // Get the media ID and set it for the Spatie component
                        $media = Media::find($imageData['media_id']);
                        if ($media) {
                            $set($this->getName(), [$media->uuid]);
                        }
                    }
                })
        );
    }
}

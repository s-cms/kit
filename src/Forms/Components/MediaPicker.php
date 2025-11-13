<?php

namespace SmartCms\Kit\Forms\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Storage;
use SmartCms\Kit\Models\Media;
use SmartCms\Kit\Services\MediaLibraryService;

class MediaPicker extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->searchable()
            ->preload()
            ->native(false)
            ->getSearchResultsUsing(function (string $search): array {
                return Media::query()
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%")
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(fn (Media $media) => [
                        $media->id => $media->name . ' (' . $media->file_name . ')',
                    ])
                    ->toArray();
            })
            ->getOptionLabelUsing(function ($value): string {
                $media = Media::find($value);

                return $media ? $media->name . ' (' . $media->file_name . ')' : '';
            })
            ->createOptionForm([
                Tabs::make('media_tabs')
                    ->tabs([
                        Tab::make(__('kit::admin.upload'))
                            ->schema([
                                FileUpload::make('upload_file')
                                    ->label(__('kit::admin.image'))
                                    ->image()
                                    ->disk(config('kit.media.disk', 'public'))
                                    ->directory('temp')
                                    ->required()
                                    ->acceptedFileTypes(['image/*'])
                                    ->maxSize(10240),

                                TextInput::make('upload_name')
                                    ->label(__('kit::admin.name'))
                                    ->placeholder(__('kit::admin.optional')),
                            ]),

                        Tab::make(__('kit::admin.import_from_url'))
                            ->schema([
                                TextInput::make('url_input')
                                    ->label(__('kit::admin.image_url'))
                                    ->url()
                                    ->required()
                                    ->placeholder('https://example.com/image.jpg'),

                                TextInput::make('url_name')
                                    ->label(__('kit::admin.name'))
                                    ->placeholder(__('kit::admin.optional')),
                            ]),
                    ])
                    ->contained(false),
            ])
            ->createOptionUsing(function (array $data): int {
                $service = app(MediaLibraryService::class);

                // Check which tab was used
                if (! empty($data['upload_file'])) {
                    // Handle file upload
                    $disk = config('kit.media.disk', 'public');
                    $tempPath = $data['upload_file'];

                    // Get the temporary file
                    $file = Storage::disk($disk)->get($tempPath);
                    $mimeType = Storage::disk($disk)->mimeType($tempPath);
                    $size = Storage::disk($disk)->size($tempPath);

                    // Generate file name
                    $baseName = $data['upload_name'] ?? pathinfo($tempPath, PATHINFO_FILENAME);
                    $extension = pathinfo($tempPath, PATHINFO_EXTENSION);
                    $slug = \Illuminate\Support\Str::slug($baseName);
                    $hash = substr(md5($file), 0, 8);
                    $fileName = $slug . '-' . $hash . '.' . $extension;

                    // Generate path
                    $path = config('kit.media.collection_name', 'library') . '/' . date('Y/m');

                    // Move from temp to final location
                    Storage::disk($disk)->put($path . '/' . $fileName, $file);
                    Storage::disk($disk)->delete($tempPath);

                    // Get full path for image processing
                    $fullPath = Storage::disk($disk)->path($path . '/' . $fileName);

                    // Extract dimensions
                    $dimensions = [];
                    if (str_starts_with($mimeType, 'image/')) {
                        try {
                            $image = \Spatie\Image\Image::load($fullPath);
                            $dimensions = [
                                'width' => $image->getWidth(),
                                'height' => $image->getHeight(),
                            ];
                        } catch (\Exception $e) {
                            // Ignore dimension extraction errors
                        }
                    }

                    // Create media record
                    $media = Media::create([
                        'file_name' => $fileName,
                        'name' => $baseName,
                        'disk' => $disk,
                        'path' => $path,
                        'mime_type' => $mimeType,
                        'size' => $size,
                        'width' => $dimensions['width'] ?? null,
                        'height' => $dimensions['height'] ?? null,
                        'alt' => [],
                        'conversions' => [],
                        'responsive_images' => [],
                        'custom_properties' => [],
                    ]);

                    return $media->id;
                } elseif (! empty($data['url_input'])) {
                    // Handle URL import
                    $result = $service->storeFromUrl(
                        $data['url_input'],
                        config('kit.media.collection_name', 'library'),
                        []
                    );

                    // Update name if provided
                    if (! empty($data['url_name']) && isset($result['media_id'])) {
                        $media = Media::find($result['media_id']);
                        if ($media) {
                            $media->name = $data['url_name'];
                            $media->save();
                        }
                    }

                    return $result['media_id'];
                }

                throw new \Exception('No file or URL provided');
            });
    }

    public function getImageData(): ?array
    {
        $mediaId = $this->getState();

        if (! $mediaId) {
            return null;
        }

        $media = Media::find($mediaId);

        if (! $media) {
            return null;
        }

        $service = app(MediaLibraryService::class);

        return $service->mediaToImageArray($media);
    }
}

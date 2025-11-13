<?php

namespace SmartCms\Kit\Admin\Resources\Media\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use SmartCms\Kit\Admin\Resources\Media\MediaResource;
use SmartCms\Kit\Models\Media;
use SmartCms\Kit\Services\MediaLibraryService;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('kit::admin.upload_image'))
                ->icon('heroicon-o-plus')
                ->form([
                    Section::make(__('kit::admin.upload'))
                        ->schema([
                            FileUpload::make('upload_file')
                                ->label(__('kit::admin.image'))
                                ->image()
                                ->disk(config('kit.media.disk', 'public'))
                                ->directory('temp')
                                ->acceptedFileTypes(['image/*'])
                                ->maxSize(10240)
                                ->helperText(__('kit::admin.upload_or_url_required')),

                            TextInput::make('upload_name')
                                ->label(__('kit::admin.name'))
                                ->placeholder(__('kit::admin.optional')),
                        ]),

                    Section::make(__('kit::admin.import_from_url'))
                        ->schema([
                            TextInput::make('url_input')
                                ->label(__('kit::admin.image_url'))
                                ->url()
                                ->placeholder('https://example.com/image.jpg')
                                ->helperText(__('kit::admin.upload_or_url_required')),

                            TextInput::make('url_name')
                                ->label(__('kit::admin.name'))
                                ->placeholder(__('kit::admin.optional')),
                        ]),
                ])
                ->using(function (array $data): Media {
                    $service = app(MediaLibraryService::class);

                    // Check which source was used
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
                        return Media::create([
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

                                return $media;
                            }
                        }

                        return Media::find($result['media_id']);
                    }

                    throw new \Exception(__('kit::admin.upload_or_url_required'));
                }),

            Action::make('upload_multiple')
                ->label(__('kit::admin.upload_multiple'))
                ->icon('heroicon-o-photo')
                ->form([
                    FileUpload::make('files')
                        ->label(__('kit::admin.images'))
                        ->image()
                        ->multiple()
                        ->disk(config('kit.media.disk', 'public'))
                        ->directory('temp')
                        ->acceptedFileTypes(['image/*'])
                        ->maxSize(10240)
                        ->maxFiles(20)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $disk = config('kit.media.disk', 'public');
                    $path = config('kit.media.collection_name', 'library') . '/' . date('Y/m');
                    $created = 0;

                    foreach ($data['files'] as $tempPath) {
                        try {
                            // Get the temporary file
                            $file = Storage::disk($disk)->get($tempPath);
                            $mimeType = Storage::disk($disk)->mimeType($tempPath);
                            $size = Storage::disk($disk)->size($tempPath);

                            // Generate file name
                            $baseName = pathinfo($tempPath, PATHINFO_FILENAME);
                            $extension = pathinfo($tempPath, PATHINFO_EXTENSION);
                            $slug = \Illuminate\Support\Str::slug($baseName);
                            $hash = substr(md5($file . time()), 0, 8);
                            $fileName = $slug . '-' . $hash . '.' . $extension;

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
                            Media::create([
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

                            $created++;
                        } catch (\Exception $e) {
                            // Skip failed uploads
                            \Log::error('Failed to upload media from multiple upload', [
                                'error' => $e->getMessage(),
                                'file' => $tempPath,
                            ]);
                        }
                    }

                    \Filament\Notifications\Notification::make()
                        ->title(__('kit::admin.images_uploaded', ['count' => $created]))
                        ->success()
                        ->send();
                }),
        ];
    }
}

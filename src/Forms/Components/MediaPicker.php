<?php

namespace SmartCms\Kit\Forms\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use SmartCms\Kit\Models\Media;
use SmartCms\Kit\Services\MediaLibraryService;

class MediaPicker extends Select
{
    protected function transformMediaToOption(Media $media): array
    {
        return [
            'id' => $media->id,
            'name' => new HtmlString("<div style='display: flex; gap: 5px; align-items: center;'>
                    <img src='{$media->getUrl('thumb')}' alt='{$media->name}' style='width: 20px; height: 20px; object-fit: cover; border-radius: 50%;' >
                    <span>{$media->name}</span>
                    </div>")->toHtml(),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchable()
            ->preload()
            ->native(false)
            ->wrapOptionLabels(false)
            ->options(Media::query()->get()->mapWithKeys(fn (Media $media) => [$media->id => $this->transformMediaToOption($media)])->pluck('name', 'id')->toArray())
            // ->getSearchResultsUsing(function (string $search): array {
            //     return Media::query()
            //         ->where('name', 'like', "%{$search}%")
            //         ->orWhere('file_name', 'like', "%{$search}%")
            //         ->limit(50)
            //         ->get()
            //         ->map
            //         ->mapWithKeys(fn(Media $media) => [
            //             $media->id => $this->transformMediaToOption($media),
            //         ])
            //         ->toArray();
            // })
            // ->getOptionLabelUsing(function ($value): string |HtmlString {
            //     $media = Media::find($value);
            //     if ($media) {
            //         return new HtmlString("<div class='flex items-center gap-2'>
            //         <img src='{$media->getUrl('thumb')}' alt='{$media->name}' class='w-6 h-6 rounded-full'>
            //         <span>{$media->name}</span>
            //         </div>");
            //     }
            //     return $media ? $media->name : '';
            // })
            ->allowHtml()
            ->createOptionForm([
                Section::make()
                    ->schema([
                        FileUpload::make('upload_file')
                            ->label(__('kit::admin.image'))
                            ->image()
                            ->disk(config('kit.media.disk', 'public'))
                            ->directory('temp')
                            ->acceptedFileTypes(['image/*'])
                            ->maxSize(10240)
                            ->helperText(__('kit::admin.upload_or_url_required')),
                        Text::make('Or')->weight(FontWeight::Bold)->columnSpanFull(),
                        TextInput::make('url_input')
                            ->label(__('kit::admin.image_url'))
                            ->url()
                            ->placeholder('https://example.com/image.jpg')
                            ->helperText(__('kit::admin.upload_or_url_required')),
                        TextInput::make('upload_name')
                            ->label(__('kit::admin.name'))
                            ->placeholder(__('kit::admin.optional')),
                    ]),
            ])
            ->createOptionUsing(function (array $data): int {
                $service = app(MediaLibraryService::class);
                // Check which tab was used
                $baseName = $data['upload_name'] ?? null;
                if (! empty($data['upload_file'])) {
                    // Handle file upload
                    $disk = config('kit.media.disk', 'public');
                    $tempPath = $data['upload_file'];
                    // Get the temporary file
                    $file = Storage::disk($disk)->get($tempPath);
                    $mimeType = Storage::disk($disk)->mimeType($tempPath);
                    $size = Storage::disk($disk)->size($tempPath);

                    // Generate file name
                    if (! $baseName) {
                        $baseName = pathinfo($tempPath, PATHINFO_FILENAME);
                    }
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

                throw new \Exception(__('kit::admin.upload_or_url_required'));
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

<?php

namespace SmartCms\Kit\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Support\Facades\Storage;
use SmartCms\Kit\Models\Media;
use SmartCms\Kit\Services\MediaLibraryService;
use Spatie\Image\Image;

class MediaPicker extends Field
{
    protected string $view = 'kit::forms.components.media-picker';

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (MediaPicker $component, $state): void {
            if ($state && is_numeric($state)) {
                $component->state($state);
            }
        });

        $this->dehydrateStateUsing(function ($state) {
            return $state ?: null;
        });
    }

    public function getMediaPreview(): ?array
    {
        $mediaId = $this->getState();

        if (! $mediaId) {
            return null;
        }

        $media = Media::find($mediaId);

        if (! $media) {
            return null;
        }

        return [
            'id' => $media->id,
            'name' => $media->name,
            'url' => $media->getUrl(),
            'alt_translations' => $media->alt ?? [],
            'width' => $media->width,
            'height' => $media->height,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
        ];
    }

    public static function getMediaLibrary(?string $search = null, int $limit = 24): array
    {
        $query = Media::query()->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        return $query->limit($limit)->get()->map(function (Media $media) {
            return [
                'id' => $media->id,
                'name' => $media->name,
                'url' => $media->getUrl(),
                'mime_type' => $media->mime_type,
            ];
        })->toArray();
    }

    public static function uploadFile($file): ?int
    {
        if (! $file) {
            return null;
        }

        $service = app(MediaLibraryService::class);
        $disk = config('kit.media.disk', 'public');

        $tempPath = $file->store('temp', $disk);
        $fileContent = Storage::disk($disk)->get($tempPath);
        $mimeType = Storage::disk($disk)->mimeType($tempPath);
        $size = Storage::disk($disk)->size($tempPath);

        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $slug = \Illuminate\Support\Str::slug($baseName);
        $hash = substr(md5($fileContent), 0, 8);
        $fileName = $slug . '-' . $hash . '.' . $extension;

        $path = config('kit.media.collection_name', 'library');

        Storage::disk($disk)->put($path . '/' . $fileName, $fileContent);
        Storage::disk($disk)->delete($tempPath);

        $fullPath = Storage::disk($disk)->path($path . '/' . $fileName);

        $dimensions = [];
        if (str_starts_with($mimeType, 'image/')) {
            try {
                $image = \Spatie\Image\Image::load($fullPath);
                $dimensions = [
                    'width' => $image->getWidth(),
                    'height' => $image->getHeight(),
                ];
            } catch (\Exception $e) {
            }
        }

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
    }
}

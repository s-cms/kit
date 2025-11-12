<?php

namespace SmartCms\Kit\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibraryService
{
    /**
     * Container model for media library items
     */
    protected function getMediaContainer(): HasMedia
    {
        // Create a simple container model for media library
        return new class extends Model implements HasMedia
        {
            use InteractsWithMedia;

            protected $table = 'pages'; // Use existing table, we just need an ID

            public function registerMediaCollections(): void
            {
                $this->addMediaCollection(config('kit.media.collection_name', 'library'));
            }

            public function registerMediaConversions(?Media $media = null): void
            {
                $conversions = config('kit.media.conversions', []);

                foreach ($conversions as $name => $settings) {
                    $conversion = $this->addMediaConversion($name)
                        ->width($settings['width'])
                        ->height($settings['height'])
                        ->nonQueued();

                    if (isset($settings['format']) && $settings['format']) {
                        $conversion->format($settings['format']);
                    }
                }
            }
        };
    }

    /**
     * Store an uploaded file
     */
    public function storeUploadedFile(UploadedFile $file, string $collection = 'library'): array
    {
        $container = $this->getMediaContainer();
        $container->id = 1; // Use a fixed ID for the container

        $media = $container->addMedia($file)
            ->toMediaCollection($collection);

        return $media->toImageArray();
    }

    /**
     * Store image from URL
     */
    public function storeFromUrl(string $url, string $collection = 'library', array $customProperties = []): array
    {
        // Download the image
        $response = Http::timeout(30)->get($url);

        if (! $response->successful()) {
            throw new \Exception('Failed to download image from URL');
        }

        // Get content and extension
        $content = $response->body();
        $contentType = $response->header('Content-Type');

        // Validate it's an image
        if (! str_starts_with($contentType, 'image/')) {
            throw new \Exception('URL does not point to an image');
        }

        // Determine extension from content type
        $extension = match ($contentType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => 'jpg',
        };

        // Generate filename
        $filename = Str::slug(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME) ?: 'image') . '.' . $extension;

        // Store temporarily
        $tempPath = sys_get_temp_dir() . '/' . uniqid() . '_' . $filename;
        file_put_contents($tempPath, $content);

        try {
            $container = $this->getMediaContainer();
            $container->id = 1;

            $media = $container->addMedia($tempPath)
                ->usingFileName($filename)
                ->withCustomProperties(array_merge(
                    ['source' => 'url'],
                    $customProperties
                ))
                ->toMediaCollection($collection);

            return $media->toImageArray();
        } finally {
            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    /**
     * Get media by ID
     */
    public function getMediaById(int $id): ?Media
    {
        return Media::find($id);
    }

    /**
     * Delete media
     */
    public function deleteMedia(int $id): bool
    {
        $media = $this->getMediaById($id);

        if (! $media) {
            return false;
        }

        return $media->delete();
    }

    /**
     * Update media alt text
     */
    public function updateAltText(int $id, array $alt): bool
    {
        $media = $this->getMediaById($id);

        if (! $media) {
            return false;
        }

        $media->setCustomProperty('alt', $alt);
        $media->save();

        return true;
    }

    /**
     * Search media library
     */
    public function search(string $query, string $collection = 'library', int $limit = 20)
    {
        return Media::query()
            ->where('collection_name', $collection)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('file_name', 'like', "%{$query}%");
            })
            ->latest()
            ->limit($limit)
            ->get();
    }
}

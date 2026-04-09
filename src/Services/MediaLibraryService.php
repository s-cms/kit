<?php

namespace SmartCms\Kit\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SmartCms\Kit\Models\Media;
use Spatie\Image\Image as SpatieImage;

class MediaLibraryService
{
    /**
     * Store an uploaded file
     */
    public function storeUploadedFile(UploadedFile $file, string $collection = 'library'): array
    {
        $disk = config('kit.media.disk', 'public');
        $fileName = $this->generateFileName($file);
        $path = $this->generatePath($collection, $file->getMimeType());

        // Store the file
        Storage::disk($disk)->putFileAs($path, $file, $fileName);

        // Get full path for image processing
        $fullPath = Storage::disk($disk)->path($path . '/' . $fileName);

        // Extract dimensions if it's an image
        $dimensions = $this->extractDimensions($fullPath, $file->getMimeType());

        // Create media record
        $media = Media::create([
            'file_name' => $fileName,
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $dimensions['width'] ?? null,
            'height' => $dimensions['height'] ?? null,
            'alt' => [],
            'conversions' => [],
            'responsive_images' => [],
            'custom_properties' => [],
        ]);

        return $this->mediaToImageArray($media);
    }

    /**
     * Store image from URL
     */
    public function storeFromUrl(string $url, string $collection = 'library', array $customProperties = []): array
    {
        // Reject non-HTTP(S) schemes to prevent file://, gopher://, etc.
        if (! preg_match('#^https?://#i', $url)) {
            throw new \Exception('Only HTTP(S) URLs are allowed');
        }

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
        $baseName = Str::slug(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME) ?: 'image');
        $fileName = $baseName . '.' . $extension;

        // Store temporarily
        $tempPath = sys_get_temp_dir() . '/' . uniqid() . '_' . $fileName;
        file_put_contents($tempPath, $content);

        try {
            $disk = config('kit.media.disk', 'public');
            $path = $this->generatePath($collection, $contentType);

            // Store the file
            Storage::disk($disk)->put($path . '/' . $fileName, file_get_contents($tempPath));

            // Get full path for image processing
            $fullPath = Storage::disk($disk)->path($path . '/' . $fileName);

            // Extract dimensions
            $dimensions = $this->extractDimensions($fullPath, $contentType);

            // Create media record
            $media = Media::create([
                'file_name' => $fileName,
                'name' => $baseName,
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $contentType,
                'size' => strlen($content),
                'width' => $dimensions['width'] ?? null,
                'height' => $dimensions['height'] ?? null,
                'alt' => [],
                'conversions' => [],
                'responsive_images' => [],
                'custom_properties' => array_merge(
                    ['source' => 'url'],
                    $customProperties
                ),
            ]);

            return $this->mediaToImageArray($media);
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

        return $media->deleteWithFiles();
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

        $media->alt = $alt;
        $media->save();

        return true;
    }

    /**
     * Search media library
     */
    public function search(string $query, string $collection = 'library', int $limit = 20)
    {
        return Media::query()
            ->where('path', 'like', "%/{$collection}%")
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('file_name', 'like', "%{$query}%");
            })
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Convert Media to image array format
     */
    public function mediaToImageArray(Media $media): array
    {
        return [
            'source' => $media->getUrl(),
            'width' => $media->width ?? 0,
            'height' => $media->height ?? 0,
            'alt' => $media->alt ?? [],
            'media_id' => $media->id,
        ];
    }

    /**
     * Generate a unique file name
     */
    protected function generateFileName(UploadedFile $file): string
    {
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $slug = Str::slug($baseName);
        $hash = substr(md5($file->getContent()), 0, 8);

        return $slug . '-' . $hash . '.' . $extension;
    }

    /**
     * Generate storage path for collection, sub-folder by mime type group
     */
    protected function generatePath(string $collection, ?string $mimeType = null): string
    {
        $typeFolder = $this->getTypeFolderForMime($mimeType);

        return $collection . '/' . $typeFolder;
    }

    protected function getTypeFolderForMime(?string $mimeType): string
    {
        if (! $mimeType) {
            return 'images';
        }

        if (str_starts_with($mimeType, 'image/')) {
            return 'images';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        return 'documents';
    }

    /**
     * Extract dimensions from image file
     */
    protected function extractDimensions(string $path, string $mimeType): array
    {
        if (! str_starts_with($mimeType, 'image/')) {
            return [];
        }

        try {
            $image = SpatieImage::load($path);

            return [
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}

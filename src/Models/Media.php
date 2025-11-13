<?php

namespace SmartCms\Kit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Image;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'uuid',
        'file_name',
        'name',
        'disk',
        'path',
        'mime_type',
        'size',
        'width',
        'height',
        'alt',
        'conversions',
        'responsive_images',
        'custom_properties',
    ];

    protected $casts = [
        'alt' => 'array',
        'conversions' => 'array',
        'responsive_images' => 'array',
        'custom_properties' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Media $media) {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the full URL to the original file
     */
    public function getUrl(): string
    {
        return Storage::disk($this->disk)->url($this->path . '/' . $this->file_name);
    }

    /**
     * Get the full URL to a conversion
     */
    public function getConversionUrl(string $conversion): string
    {
        $conversions = $this->conversions ?? [];

        if (! isset($conversions[$conversion])) {
            return $this->getUrl();
        }

        return Storage::disk($this->disk)->url($this->path . '/' . $conversions[$conversion]);
    }

    /**
     * Get the full path to the original file
     */
    public function getPath(): string
    {
        return Storage::disk($this->disk)->path($this->path . '/' . $this->file_name);
    }

    /**
     * Get the full path to a conversion
     */
    public function getConversionPath(string $conversion): string
    {
        $conversions = $this->conversions ?? [];

        if (! isset($conversions[$conversion])) {
            return $this->getPath();
        }

        return Storage::disk($this->disk)->path($this->path . '/' . $conversions[$conversion]);
    }

    /**
     * Get a custom property value
     */
    public function getCustomProperty(string $key, mixed $default = null): mixed
    {
        $properties = $this->custom_properties ?? [];

        return $properties[$key] ?? $default;
    }

    /**
     * Set a custom property value
     */
    public function setCustomProperty(string $key, mixed $value): self
    {
        $properties = $this->custom_properties ?? [];
        $properties[$key] = $value;
        $this->custom_properties = $properties;

        return $this;
    }

    /**
     * Check if this is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Get responsive image URLs
     */
    public function getResponsiveImageUrls(): array
    {
        $responsiveImages = $this->responsive_images ?? [];
        $urls = [];

        foreach ($responsiveImages as $fileName) {
            $urls[] = Storage::disk($this->disk)->url($this->path . '/' . $fileName);
        }

        return $urls;
    }

    /**
     * Get srcset attribute for responsive images
     */
    public function getSrcset(): string
    {
        $responsiveImages = $this->responsive_images ?? [];
        $srcset = [];

        foreach ($responsiveImages as $fileName) {
            // Extract width from filename (format: name___w_1200.webp)
            if (preg_match('/___w_(\d+)\./', $fileName, $matches)) {
                $width = $matches[1];
                $url = Storage::disk($this->disk)->url($this->path . '/' . $fileName);
                $srcset[] = "{$url} {$width}w";
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Delete the media and all its files
     */
    public function deleteWithFiles(): bool
    {
        $disk = Storage::disk($this->disk);

        // Delete original file
        if ($disk->exists($this->path . '/' . $this->file_name)) {
            $disk->delete($this->path . '/' . $this->file_name);
        }

        // Delete conversions
        foreach ($this->conversions ?? [] as $fileName) {
            if ($disk->exists($this->path . '/' . $fileName)) {
                $disk->delete($this->path . '/' . $fileName);
            }
        }

        // Delete responsive images
        foreach ($this->responsive_images ?? [] as $fileName) {
            if ($disk->exists($this->path . '/' . $fileName)) {
                $disk->delete($this->path . '/' . $fileName);
            }
        }

        // Try to delete the directory if empty
        try {
            if (empty($disk->files($this->path))) {
                $disk->deleteDirectory($this->path);
            }
        } catch (\Exception $e) {
            // Ignore errors when trying to delete directory
        }

        return $this->delete();
    }
}

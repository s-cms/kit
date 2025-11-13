<?php

namespace SmartCms\Kit\Services;

use Illuminate\Support\Facades\Storage;
use SmartCms\Kit\Models\Media;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class ImageProcessingService
{
    /**
     * Process image: convert original to WebP and create responsive images
     */
    public function processImage(Media $media): void
    {
        if (! $media->isImage()) {
            return;
        }

        // Skip SVG files
        if ($media->mime_type === 'image/svg+xml') {
            return;
        }

        $disk = Storage::disk($media->disk);
        $originalPath = $disk->path($media->path . '/' . $media->file_name);

        if (! file_exists($originalPath)) {
            return;
        }

        try {
            // Convert original to WebP (replaces the original file)
            $webpFileName = $this->convertOriginalToWebP($media, $originalPath);

            if ($webpFileName) {
                // Update media record with new WebP file name
                $media->file_name = $webpFileName;
                $media->mime_type = 'image/webp';

                // Get new path for WebP file
                $webpPath = $disk->path($media->path . '/' . $webpFileName);

                // Generate responsive images from WebP original
                $responsiveImages = $this->generateResponsiveImages($media, $webpPath);
                $media->responsive_images = $responsiveImages;

                $media->save();
            }
        } catch (\Exception $e) {
            // Log error but don't fail the upload
            \Log::error('Image processing failed for media ' . $media->id, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Convert original image to WebP format (replaces original)
     */
    protected function convertOriginalToWebP(Media $media, string $originalPath): ?string
    {
        try {
            // Skip if already WebP
            if ($media->mime_type === 'image/webp') {
                return $media->file_name;
            }

            $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
            $webpFileName = $baseName . '.webp';

            $image = Image::load($originalPath);
            $webpPath = Storage::disk($media->disk)->path($media->path . '/' . $webpFileName);

            $image->optimize()->format('webp');
            $image->save($webpPath);

            // Delete the original non-WebP file
            if (file_exists($originalPath) && $originalPath !== $webpPath) {
                @unlink($originalPath);
            }

            return $webpFileName;
        } catch (\Exception $e) {
            \Log::error('WebP conversion failed for media ' . $media->id, [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate responsive images at various widths from WebP original
     */
    protected function generateResponsiveImages(Media $media, string $webpPath): array
    {
        $widths = $this->getResponsiveWidths($media->width);
        $responsiveImages = [];

        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);

        foreach ($widths as $width) {
            try {
                $fileName = $baseName . '___w_' . $width . '.webp';
                $targetPath = Storage::disk($media->disk)->path($media->path . '/' . $fileName);

                $image = Image::load($webpPath);

                // Calculate proportional height
                $height = (int) round(($width / $media->width) * $media->height);

                $image->fit(Fit::Max, $width, $height);
                $image->optimize()->format('webp');
                $image->save($targetPath);

                $responsiveImages[] = $fileName;
            } catch (\Exception $e) {
                // Skip this width if it fails
                \Log::warning('Failed to generate responsive image at width ' . $width . ' for media ' . $media->id, [
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return $responsiveImages;
    }

    /**
     * Calculate responsive image widths based on Spatie's logic
     * This generates widths that are smaller than the original
     */
    protected function getResponsiveWidths(int $originalWidth): array
    {
        // Default widths similar to Spatie's responsive images
        $defaultWidths = [340, 540, 720, 1024, 1366, 1600, 1920];

        // Only use widths smaller than the original
        return array_filter($defaultWidths, fn ($width) => $width < $originalWidth);
    }
}

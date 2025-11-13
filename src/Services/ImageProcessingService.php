<?php

namespace SmartCms\Kit\Services;

use Illuminate\Support\Facades\Storage;
use SmartCms\Kit\Models\Media;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class ImageProcessingService
{
    /**
     * Process image: create WebP conversion and responsive images
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

        $conversions = [];
        $responsiveImages = [];

        try {
            // Create WebP conversion
            $webpFileName = $this->createWebPConversion($media, $originalPath);
            if ($webpFileName) {
                $conversions['webp'] = $webpFileName;
            }

            // Generate responsive images
            $responsiveFileNames = $this->generateResponsiveImages($media, $originalPath);
            $responsiveImages = $responsiveFileNames;

            // Create thumbnail for admin preview
            $thumbFileName = $this->createThumbnail($media, $originalPath);
            if ($thumbFileName) {
                $conversions['thumb'] = $thumbFileName;
            }

            // Update media record with conversions
            $media->conversions = $conversions;
            $media->responsive_images = $responsiveImages;
            $media->save();
        } catch (\Exception $e) {
            // Log error but don't fail the upload
            \Log::error('Image processing failed for media ' . $media->id, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create WebP conversion of the original image
     */
    protected function createWebPConversion(Media $media, string $originalPath): ?string
    {
        try {
            $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
            $webpFileName = $baseName . '.webp';

            $image = Image::load($originalPath);

            $webpPath = Storage::disk($media->disk)->path($media->path . '/' . $webpFileName);
            $image->optimize()->format(
                'webp'
            );

            $image->save($webpPath);

            return $webpFileName;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate responsive images at various widths
     */
    protected function generateResponsiveImages(Media $media, string $originalPath): array
    {
        $widths = $this->getResponsiveWidths($media->width);
        $responsiveImages = [];

        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);

        foreach ($widths as $width) {
            try {
                $fileName = $baseName . '___w_' . $width . '.webp';
                $targetPath = Storage::disk($media->disk)->path($media->path . '/' . $fileName);

                $image = Image::load($originalPath);
                // Calculate proportional height
                $height = (int) round(($width / $media->width) * $media->height);

                $image->fit(Fit::Max, $width, $height);
                $image->optimize()->format(
                    'webp'
                );
                $image->save($targetPath);

                $responsiveImages[] = $fileName;
            } catch (\Exception $e) {
                // Skip this width if it fails
                continue;
            }
        }

        return $responsiveImages;
    }

    /**
     * Create thumbnail for admin preview
     */
    protected function createThumbnail(Media $media, string $originalPath): ?string
    {
        try {
            $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
            $thumbFileName = $baseName . '-thumb.jpg';

            $image = Image::load($originalPath);
            $thumbPath = Storage::disk($media->disk)->path($media->path . '/' . $thumbFileName);

            $image->fit(Fit::Contain, 150, 150);
            $image->save($thumbPath);

            return $thumbFileName;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calculate responsive image widths based on Spatie's logic
     * This generates widths that are smaller than the original
     */
    protected function getResponsiveWidths(int $originalWidth): array
    {
        // Default widths similar to Spatie's responsive images
        $defaultWidths = [540, 720, 1366, 1920];

        // Only use widths smaller than the original
        return array_filter($defaultWidths, fn($width) => $width < $originalWidth);
    }
}

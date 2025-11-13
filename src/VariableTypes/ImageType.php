<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use SmartCms\Kit\Forms\Components\MediaPicker;
use SmartCms\Kit\Models\Media;
use SmartCms\Kit\Services\MediaLibraryService;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class ImageType implements VariableTypeInterface
{
    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'image';
    }

    public function getDefaultValue(): mixed
    {
        return [
            'width' => 300,
            'height' => 300,
            'source' => asset('favicon.ico'),
            'alt' => 'default image',
        ];
    }

    public function getSchema(string $name): Field | Component
    {
        return MediaPicker::make($name)
            ->label('Image');
    }

    public function getValue(mixed $value): mixed
    {
        // If value is a media_id (integer), fetch the media data
        if (is_numeric($value)) {
            $media = Media::find($value);

            if (! $media) {
                return $this->getDefaultValue();
            }

            $service = app(MediaLibraryService::class);
            $imageData = $service->mediaToImageArray($media);

            // Get alt text for current language
            $alt = $imageData['alt'][current_lang()] ?? $imageData['alt']['alt'] ?? __('Default alt');

            return [
                'source' => $imageData['source'],
                'width' => $imageData['width'],
                'height' => $imageData['height'],
                'alt' => $alt,
                'srcset' => $media->getSrcset(),
                'media_id' => $media->id,
            ];
        }

        // Legacy support: if value is already an array (old JSON format)
        if (is_array($value)) {
            // Check if it has media_id, then fetch fresh data
            if (isset($value['media_id'])) {
                $media = Media::find($value['media_id']);

                if ($media) {
                    $service = app(MediaLibraryService::class);
                    $imageData = $service->mediaToImageArray($media);

                    $alt = $imageData['alt'][current_lang()] ?? $imageData['alt']['alt'] ?? __('Default alt');

                    return [
                        'source' => $imageData['source'],
                        'width' => $imageData['width'],
                        'height' => $imageData['height'],
                        'alt' => $alt,
                        'srcset' => $media->getSrcset(),
                        'media_id' => $media->id,
                    ];
                }
            }

            // Old format without media_id, validate and return as-is
            $alt = $value[current_lang()] ?? $value['alt'] ?? __('Default alt');

            if (! isset($value['source']) || ! isset($value['width']) || ! isset($value['height'])) {
                return $this->getDefaultValue();
            }

            $image = validateImage($value);
            $image['alt'] = $alt;

            return $image;
        }

        // Invalid value, return default
        return $this->getDefaultValue();
    }
}

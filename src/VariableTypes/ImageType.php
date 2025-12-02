<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use SmartCms\Kit\Forms\Components\MediaPicker;
use SmartCms\Kit\Http\Resources\MediaResource;
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
        return MediaResource::make($value)->toArray(request());
    }
}

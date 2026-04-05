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

    public function getSchema(string $name, ?string $language = null): Field | Component
    {
        return MediaPicker::make($name . '.id')
            ->label('Image');
    }

    public function getValue(mixed $value): mixed
    {
        $id = $value['id'] ?? null;
        if (! is_array($value) && is_string($value)) {
            $id = $value;
        }

        if (! $id) {
            return $this->getDefaultValue();
        }

        return MediaResource::make($id)->toArray(request());
    }
}

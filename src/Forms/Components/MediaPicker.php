<?php

namespace SmartCms\Kit\Forms\Components;

use Filament\Forms\Components\Field;
use SmartCms\Kit\Models\Media;

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
}

<?php

namespace SmartCms\Kit\Filament\Forms\Components;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Illuminate\Support\Facades\Http;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Str;

class MediaPicker extends Field
{
    protected string $view = 'kit::filament.forms.media-picker';

    protected bool $enableUpload = true;

    protected bool $enableUrl = true;

    protected bool $enableUnsplash = true;

    protected bool $enableLibrary = true;

    protected ?string $collection = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collection = config('kit.media.collection_name', 'library');

        $this->afterStateHydrated(function (MediaPicker $component, $state) {
            // If state is a media ID, load the media
            if (is_numeric($state)) {
                $media = Media::find($state);
                if ($media) {
                    $component->state($media->toImageArray());
                }
            }
        });

        $this->dehydrateStateUsing(function ($state) {
            // Convert to image array format for storage
            if ($state && isset($state['media_id'])) {
                return $state;
            }

            return $state;
        });
    }

    public function enableUpload(bool $condition = true): static
    {
        $this->enableUpload = $condition;

        return $this;
    }

    public function enableUrl(bool $condition = true): static
    {
        $this->enableUrl = $condition;

        return $this;
    }

    public function enableUnsplash(bool $condition = true): static
    {
        $this->enableUnsplash = $condition;

        return $this;
    }

    public function enableLibrary(bool $condition = true): static
    {
        $this->enableLibrary = $condition;

        return $this;
    }

    public function collection(string $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function getCollection(): string
    {
        return $this->collection;
    }

    public function isUploadEnabled(): bool
    {
        return $this->enableUpload;
    }

    public function isUrlEnabled(): bool
    {
        return $this->enableUrl;
    }

    public function isUnsplashEnabled(): bool
    {
        return $this->enableUnsplash && config('kit.unsplash.enabled', false);
    }

    public function isLibraryEnabled(): bool
    {
        return $this->enableLibrary;
    }
}

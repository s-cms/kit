<?php

namespace SmartCms\Kit\Admin\Resources\Media\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use SmartCms\Kit\Admin\Resources\Media\MediaResource;
use SmartCms\Kit\Models\Media;
use SmartCms\Kit\Services\ImageProcessingService;
use SmartCms\Kit\Services\MediaLibraryService;
use Spatie\Image\Image;

class ListMedia extends ListRecords
{
    use WithFileUploads;

    protected static string $resource = MediaResource::class;

    protected string $view = 'kit::admin.media.gallery';

    public string $search = '';

    public string $currentPath = '';

    public string $mediaTab = 'images';

    public array $selected = [];

    public bool $moveMode = false;

    public string $newFolderName = '';

    public $uploadFiles = [];

    protected static array $mediaTypes = [
        'images' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp', 'image/tiff'],
        'video' => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo'],
        'documents' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain'],
    ];

    public function mount(): void
    {
        $this->currentPath = request()->query('path', '');
        $this->mediaTab = request()->query('tab', 'images');
    }

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.media_library');
    }

    public function getHeading(): string
    {
        return __('kit::admin.media_library');
    }

    public function getBasePath(): string
    {
        return config('kit.media.collection_name', 'library');
    }

    public function getTabBasePath(): string
    {
        return $this->getBasePath() . '/' . $this->mediaTab;
    }

    public function getFullPath(): string
    {
        $base = $this->getTabBasePath();

        return $this->currentPath ? $base . '/' . $this->currentPath : $base;
    }

    public static function getTypeFolder(string $mimeType): string
    {
        foreach (static::$mediaTypes as $folder => $types) {
            if (in_array($mimeType, $types)) {
                return $folder;
            }
        }

        return 'documents';
    }

    public static function getAcceptAttribute(string $tab): string
    {
        return match ($tab) {
            'images' => 'image/*',
            'video' => 'video/*',
            'documents' => '.pdf,.doc,.docx,.xls,.xlsx,.txt',
            default => '*/*',
        };
    }

    public function switchTab(string $tab): void
    {
        $this->mediaTab = $tab;
        $this->currentPath = '';
        $this->selected = [];
        $this->moveMode = false;
    }

    public function getFolders(): array
    {
        $disk = Storage::disk(config('kit.media.disk', 'public'));
        $fullPath = $this->getFullPath();

        if (! $disk->exists($fullPath)) {
            $disk->makeDirectory($fullPath);

            return [];
        }

        return collect($disk->directories($fullPath))
            ->map(fn (string $dir) => [
                'name' => basename($dir),
                'path' => str_replace($this->getTabBasePath() . '/', '', $dir),
            ])
            ->sortBy('name')
            ->values()
            ->toArray();
    }

    public function getMedia()
    {
        $query = Media::query()
            ->where('path', $this->getFullPath())
            ->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('file_name', 'like', "%{$this->search}%");
            });
        }

        return $query->paginate(24);
    }

    public function getBreadcrumbParts(): array
    {
        if (! $this->currentPath) {
            return [];
        }

        $parts = explode('/', $this->currentPath);
        $breadcrumbs = [];
        $accumulated = '';

        foreach ($parts as $part) {
            $accumulated = $accumulated ? $accumulated . '/' . $part : $part;
            $breadcrumbs[] = [
                'name' => $part,
                'path' => $accumulated,
            ];
        }

        return $breadcrumbs;
    }

    public function navigateToFolder(string $path): void
    {
        $this->currentPath = $path;
        if (! $this->moveMode) {
            $this->selected = [];
        }
    }

    public function navigateUp(): void
    {
        $parts = explode('/', $this->currentPath);
        array_pop($parts);
        $this->currentPath = implode('/', $parts);
        if (! $this->moveMode) {
            $this->selected = [];
        }
    }

    public function createFolder(): void
    {
        if (empty($this->newFolderName)) {
            return;
        }

        $disk = Storage::disk(config('kit.media.disk', 'public'));
        $folderPath = $this->getFullPath() . '/' . Str::slug($this->newFolderName);

        $disk->makeDirectory($folderPath);
        $this->newFolderName = '';

        Notification::make()
            ->success()
            ->title(__('kit::admin.folder_created'))
            ->send();
    }

    public function isFolderEmpty(string $path): bool
    {
        $disk = Storage::disk(config('kit.media.disk', 'public'));
        $fullPath = $this->getTabBasePath() . '/' . $path;

        return count($disk->files($fullPath)) === 0 && count($disk->directories($fullPath)) === 0;
    }

    public function deleteFolder(string $path, bool $force = false): void
    {
        // Protect root type folders
        if (in_array($path, ['images', 'video', 'documents']) || empty($path)) {
            Notification::make()
                ->danger()
                ->title(__('kit::admin.cannot_delete_system_folder'))
                ->send();

            return;
        }

        $disk = Storage::disk(config('kit.media.disk', 'public'));
        $fullPath = $this->getTabBasePath() . '/' . $path;

        $files = $disk->files($fullPath);
        $subdirs = $disk->directories($fullPath);

        if ((count($files) > 0 || count($subdirs) > 0) && ! $force) {
            Notification::make()
                ->danger()
                ->title(__('kit::admin.folder_not_empty'))
                ->send();

            return;
        }

        if ($force) {
            Media::where('path', $fullPath)->each(fn ($m) => $m->deleteWithFiles());
            foreach ($subdirs as $subdir) {
                $subPath = str_replace($this->getTabBasePath() . '/', '', $subdir);
                $this->deleteFolder($subPath, true);
            }
        }

        $disk->deleteDirectory($fullPath);

        Notification::make()
            ->success()
            ->title(__('kit::admin.folder_deleted'))
            ->send();
    }

    public function processMedia(int $id): void
    {
        $media = Media::find($id);
        if (! $media || ! $media->isImage() || $media->mime_type === 'image/svg+xml') {
            return;
        }

        $service = app(ImageProcessingService::class);
        $service->processImage($media);

        Notification::make()
            ->success()
            ->title(__('kit::admin.responsive_images_regenerated'))
            ->send();
    }

    public function processAll(): void
    {
        $service = app(ImageProcessingService::class);
        $count = 0;

        Media::query()
            ->where('mime_type', 'like', 'image/%')
            ->where('mime_type', '!=', 'image/svg+xml')
            ->where(function ($q) {
                $q->whereNull('responsive_images')
                    ->orWhere('responsive_images', '[]')
                    ->orWhere('responsive_images', '');
            })
            ->chunkById(50, function ($items) use ($service, &$count) {
                foreach ($items as $media) {
                    $service->processImage($media);
                    $count++;
                }
            });

        Notification::make()
            ->success()
            ->title(__('kit::admin.media_processed', ['count' => $count]))
            ->send();
    }

    public function getUnprocessedCount(): int
    {
        return Media::query()
            ->where('mime_type', 'like', 'image/%')
            ->where('mime_type', '!=', 'image/svg+xml')
            ->where(function ($q) {
                $q->whereNull('responsive_images')
                    ->orWhere('responsive_images', '[]')
                    ->orWhere('responsive_images', '');
            })
            ->count();
    }

    public function scanDisk(): void
    {
        $disk = Storage::disk(config('kit.media.disk', 'public'));
        $fullPath = $this->getFullPath();
        $found = 0;

        $files = $disk->files($fullPath);

        foreach ($files as $filePath) {
            $fileName = basename($filePath);

            // Skip responsive image variants (e.g. image___w_340.webp)
            if (preg_match('/___w_\d+\./', $fileName)) {
                continue;
            }

            // Skip hidden files
            if (str_starts_with($fileName, '.')) {
                continue;
            }

            $exists = Media::where('path', $fullPath)
                ->where('file_name', $fileName)
                ->exists();

            if ($exists) {
                continue;
            }

            $absolutePath = $disk->path($filePath);
            $mimeType = $disk->mimeType($filePath);
            $size = $disk->size($filePath);

            $dimensions = [];
            if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml') {
                try {
                    $image = Image::load($absolutePath);
                    $dimensions = [
                        'width' => $image->getWidth(),
                        'height' => $image->getHeight(),
                    ];
                } catch (\Exception $e) {
                }
            }

            Media::create([
                'file_name' => $fileName,
                'name' => pathinfo($fileName, PATHINFO_FILENAME),
                'disk' => config('kit.media.disk', 'public'),
                'path' => $fullPath,
                'mime_type' => $mimeType,
                'size' => $size,
                'width' => $dimensions['width'] ?? null,
                'height' => $dimensions['height'] ?? null,
                'alt' => [],
                'conversions' => [],
                'responsive_images' => [],
                'custom_properties' => [],
            ]);

            $found++;
        }

        Notification::make()
            ->success()
            ->title(__('kit::admin.scan_completed', ['count' => $found]))
            ->send();
    }

    public function deleteSelected(): void
    {
        $media = Media::whereIn('id', $this->selected)->get();

        foreach ($media as $item) {
            $item->deleteWithFiles();
        }

        $count = $media->count();
        $this->selected = [];

        Notification::make()
            ->success()
            ->title(__('kit::admin.media_deleted', ['count' => $count]))
            ->send();
    }

    public function deleteSingle(int $id): void
    {
        $media = Media::find($id);
        if ($media) {
            $media->deleteWithFiles();

            Notification::make()
                ->success()
                ->title(__('kit::admin.media_deleted', ['count' => 1]))
                ->send();
        }
    }

    // Move mode

    public function startMove(): void
    {
        if (empty($this->selected)) {
            return;
        }
        $this->moveMode = true;
    }

    public function cancelMove(): void
    {
        $this->moveMode = false;
    }

    public function moveHere(): void
    {
        $targetPath = $this->getFullPath();
        $disk = Storage::disk(config('kit.media.disk', 'public'));
        $moved = 0;

        $mediaItems = Media::whereIn('id', $this->selected)->get();

        foreach ($mediaItems as $media) {
            if ($media->path === $targetPath) {
                continue;
            }

            $oldFullPath = $media->path . '/' . $media->file_name;
            $newFullPath = $targetPath . '/' . $media->file_name;

            if ($disk->exists($oldFullPath)) {
                $disk->move($oldFullPath, $newFullPath);
                $media->path = $targetPath;
                $media->save();
                $moved++;
            }
        }

        $this->selected = [];
        $this->moveMode = false;

        Notification::make()
            ->success()
            ->title(__('kit::admin.media_moved', ['count' => $moved]))
            ->send();
    }

    // Upload

    public function updatedUploadFiles(): void
    {
        $disk = config('kit.media.disk', 'public');
        $created = 0;

        foreach ($this->uploadFiles as $file) {
            /** @var TemporaryUploadedFile $file */
            try {
                $mimeType = $file->getMimeType();
                $typeFolder = static::getTypeFolder($mimeType);

                // If uploading from a specific tab folder, use current path
                // Otherwise auto-detect by mime type
                if ($this->mediaTab === $typeFolder) {
                    $path = $this->getFullPath();
                } else {
                    $path = $this->getBasePath() . '/' . $typeFolder;
                }

                $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $slug = Str::slug($baseName);
                if (empty($slug)) {
                    $slug = 'file-' . time();
                }
                $hash = substr(md5($file->get() . time() . $created), 0, 8);
                $fileName = $slug . '-' . $hash . '.' . $extension;

                $file->storeAs($path, $fileName, $disk);

                $fullPath = Storage::disk($disk)->path($path . '/' . $fileName);
                $dimensions = [];

                if (str_starts_with($mimeType, 'image/')) {
                    try {
                        $image = Image::load($fullPath);
                        $dimensions = [
                            'width' => $image->getWidth(),
                            'height' => $image->getHeight(),
                        ];
                    } catch (\Exception $e) {
                    }
                }

                Media::create([
                    'file_name' => $fileName,
                    'name' => $baseName,
                    'disk' => $disk,
                    'path' => $path,
                    'mime_type' => $mimeType,
                    'size' => $file->getSize(),
                    'width' => $dimensions['width'] ?? null,
                    'height' => $dimensions['height'] ?? null,
                    'alt' => [],
                    'conversions' => [],
                    'responsive_images' => [],
                    'custom_properties' => [],
                ]);

                $created++;
            } catch (\Exception $e) {
                Log::error('Media upload failed', ['error' => $e->getMessage()]);
            }
        }

        $this->uploadFiles = [];

        if ($created > 0) {
            Notification::make()
                ->success()
                ->title(__('kit::admin.images_uploaded', ['count' => $created]))
                ->send();
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public string $urlInput = '';

    public function uploadFromUrl(): void
    {
        if (empty($this->urlInput)) {
            return;
        }

        try {
            $service = app(MediaLibraryService::class);
            $result = $service->storeFromUrl(
                $this->urlInput,
                $this->getBasePath()
            );

            if (isset($result['media_id'])) {
                $media = Media::find($result['media_id']);

                // If we're in a sub-folder of the matching tab, move file there
                if ($media && $this->currentPath) {
                    $targetPath = $this->getFullPath();
                    if ($media->path !== $targetPath) {
                        $disk = Storage::disk(config('kit.media.disk', 'public'));
                        $oldFullPath = $media->path . '/' . $media->file_name;
                        $newFullPath = $targetPath . '/' . $media->file_name;

                        if ($disk->exists($oldFullPath)) {
                            $disk->move($oldFullPath, $newFullPath);
                            $media->path = $targetPath;
                            $media->save();
                        }
                    }
                }
            }

            $this->urlInput = '';

            Notification::make()
                ->success()
                ->title(__('kit::admin.images_uploaded', ['count' => 1]))
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(__('kit::admin.error'))
                ->body($e->getMessage())
                ->send();
        }
    }
}

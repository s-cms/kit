<?php

namespace SmartCms\Kit\Commands;

use Illuminate\Console\Command;
use SmartCms\Kit\Models\Media;
use SmartCms\Kit\Services\ImageProcessingService;

class ProcessMedia extends Command
{
    protected $signature = 'kit:process-media {--all : Process all images, not just unprocessed}';

    protected $description = 'Generate responsive images for media files';

    public function handle(): int
    {
        $service = app(ImageProcessingService::class);

        $query = Media::query()
            ->where('mime_type', 'like', 'image/%')
            ->where('mime_type', '!=', 'image/svg+xml');

        if (! $this->option('all')) {
            $query->where(function ($q) {
                $q->whereNull('responsive_images')
                    ->orWhere('responsive_images', '[]')
                    ->orWhere('responsive_images', '');
            });
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('No images to process.');

            return self::SUCCESS;
        }

        $this->info("Processing {$total} images...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $failed = 0;

        $query->chunkById(50, function ($items) use ($service, &$processed, &$failed, $bar) {
            foreach ($items as $media) {
                try {
                    $service->processImage($media);
                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $this->newLine();
                    $this->error("Failed: {$media->name} — {$e->getMessage()}");
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Processed: {$processed}, Failed: {$failed}");

        return self::SUCCESS;
    }
}

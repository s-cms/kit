<?php

namespace SmartCms\Kit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SmartCms\Kit\Models\Media;
use SmartCms\Kit\Services\ImageProcessingService;

class ProcessMediaJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Media $media
    ) {}

    public function handle(ImageProcessingService $service): void
    {
        $service->processImage($this->media);
    }
}

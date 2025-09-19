<?php

namespace SmartCms\Kit\Commands;

use Illuminate\Console\Command;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;

class ActivatePages extends Command
{
    protected $signature = 'activate-pages';

    protected $description = 'Activate scheduled pages';

    public function handle(): void
    {
        $now = now();
        Page::query()
            ->where('status', PageStatus::Scheduled)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', $now)
            ->update([
                'status' => PageStatus::Published->value,
            ]);
    }
}

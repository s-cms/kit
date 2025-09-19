<?php

namespace SmartCms\Kit\Actions\Support;

use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;

class InstallPackages
{
    use AsAction;

    public function __construct(private Command $command) {}

    public function handle(): void
    {
        $this->command->call('settings:install');
        $this->command->call('lang:install');
        $this->command->call('seo:install');
        $this->command->call('menu:install');
        $this->command->call('forms:install');
        $this->command->call('template-builder:install');
        $this->command->call('vendor:publish', ['--tag' => 'kit-images']);
    }
}

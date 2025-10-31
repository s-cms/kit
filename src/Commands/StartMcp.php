<?php

namespace SmartCms\Kit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class StartMcp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scms:mcp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the Smart CMS MCP server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        return Artisan::call('mcp:start smart-cms');
    }
}

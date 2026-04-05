<?php

namespace SmartCms\Kit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use SmartCms\Kit\Contracts\UpdateServiceInterface;
use SmartCms\Kit\Services\UpdateErrorHandler;
use Symfony\Component\Process\Process;

class Update extends Command
{
    protected $signature = 'scms:update {--check : Only check for updates without installing} {--force : Force update even if no updates are detected}';

    protected $description = 'Update Smart CMS Kit package';

    public function __construct(protected UpdateServiceInterface $updateService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('check')) {
            return $this->checkForUpdates();
        }

        return $this->performUpdate();
    }

    protected function checkForUpdates(): int
    {
        $this->info('Checking for updates...');

        try {
            $currentVersion = $this->updateService->getCurrentVersion();
            $latestVersion = $this->updateService->getLatestVersion();

            $this->table(['Current Version', 'Latest Version', 'Status'], [
                [
                    $currentVersion,
                    $latestVersion ?? 'Unknown',
                    $this->updateService->hasUpdatesAvailable() ? 'Update Available' : 'Up to Date',
                ],
            ]);

            if ($this->updateService->hasUpdatesAvailable()) {
                $this->warn('An update is available. Run "php artisan scms:update" to install it.');

                return self::SUCCESS;
            }

            $this->info('Your system is up to date.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to check for updates: ' . $e->getMessage());
            Log::error('Update check failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }

    protected function performUpdate(): int
    {
        $this->info('Starting Smart CMS Kit update...');

        // Check if updates are available (unless forced)
        if (! $this->option('force')) {
            try {
                if (! $this->updateService->hasUpdatesAvailable()) {
                    $this->info('No updates available. Use --force to update anyway.');

                    return self::SUCCESS;
                }
            } catch (\Exception $e) {
                $this->warn('Could not check for updates: ' . $e->getMessage());
                if (! $this->confirm('Continue with update anyway?')) {
                    return self::FAILURE;
                }
            }
        }

        // Step 1: Update Composer packages
        if (! $this->updateComposerPackages()) {
            return self::FAILURE;
        }

        // Step 2: Run migrations
        if (! $this->runMigrations()) {
            return self::FAILURE;
        }

        // Step 3: Clear caches
        $this->clearCaches();

        $this->info('✅ Smart CMS Kit update completed successfully!');

        // Show new version
        try {
            $newVersion = $this->updateService->getCurrentVersion();
            $this->info("Updated to version: {$newVersion}");
        } catch (\Exception) {
            // Don't fail the update if we can't get the version
        }

        return self::SUCCESS;
    }

    protected function updateComposerPackages(): bool
    {
        $this->info('📦 Updating Composer packages...');

        $composerHome = storage_path('composer');
        $composerCache = storage_path('composer/cache');

        if (! file_exists($composerHome)) {
            mkdir($composerHome, 0755, true);
        }

        if (! file_exists($composerCache)) {
            mkdir($composerCache, 0755, true);
        }

        $command = ['composer', 'update', 'smart-cms/kit', '--no-interaction', '--prefer-dist'];

        if ($this->output->isVerbose()) {
            $command[] = '--verbose';
        }

        $process = new Process($command);

        $process->setEnv([
            'PATH' => '/usr/local/bin:/opt/homebrew/bin:/usr/bin:' . getenv('PATH'),
            'HOME' => getenv('HOME') ?: sys_get_temp_dir(),
            'COMPOSER_HOME' => $composerHome,
            'COMPOSER_CACHE_DIR' => $composerCache,
            'COMPOSER_NO_INTERACTION' => '1', // Additional safety
        ]);

        $process->setTimeout(300);
        $process->setWorkingDirectory(base_path());

        $progressBar = $this->output->createProgressBar();
        $progressBar->setFormat('verbose');
        $progressBar->start();

        $process->run(function ($type, $buffer) use ($progressBar): void {
            if ($this->output->isVerbose()) {
                $this->output->write($buffer);
            } else {
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine();

        if (! $process->isSuccessful()) {
            $this->error('❌ Composer update failed!');

            $errorDetails = UpdateErrorHandler::handleComposerError(
                $process->getErrorOutput(),
                $process->getExitCode()
            );

            $this->error($errorDetails['message']);

            if (! empty($errorDetails['troubleshooting'])) {
                $this->warn('💡 Troubleshooting suggestions:');
                foreach ($errorDetails['troubleshooting'] as $suggestion) {
                    $this->line("  • {$suggestion}");
                }
            }

            if ($this->output->isVerbose()) {
                $this->line('Technical details:');
                $this->line($errorDetails['technical_details']);
            }

            return false;
        }

        $this->info('✅ Composer packages updated successfully');

        return true;
    }

    protected function runMigrations(): bool
    {
        $this->info('🗄️ Running database migrations...');

        try {
            $this->call('migrate', ['--force' => true]);
            $this->info('✅ Database migrations completed');

            return true;
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            Log::error('Migration failed during update', ['error' => $e->getMessage()]);

            return false;
        }
    }

    protected function clearCaches(): void
    {
        $this->info('🧹 Clearing caches...');

        $cacheCommands = [
            'config:clear' => 'Configuration cache',
            'route:clear' => 'Route cache',
            'view:clear' => 'View cache',
        ];

        foreach ($cacheCommands as $command => $description) {
            try {
                $this->call($command);
                $this->line("  ✅ {$description} cleared");
            } catch (\Exception $e) {
                $this->warn("  ⚠️ Failed to clear {$description}: " . $e->getMessage());
            }
        }
    }
}

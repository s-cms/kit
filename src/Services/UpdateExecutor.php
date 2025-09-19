<?php

namespace SmartCms\Kit\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class UpdateExecutor
{
    protected array $output = [];

    protected bool $isRunning = false;

    public function executeUpdate(): array
    {
        if ($this->isRunning) {
            return [
                'success' => false,
                'message' => 'Update is already in progress',
                'output' => [],
            ];
        }

        $this->isRunning = true;
        $this->output = [];

        try {
            $this->addOutput('Starting Smart CMS Kit update...');

            // Execute the update command
            $exitCode = Artisan::call('scms:update', ['--force' => true]);

            // Get the command output
            $commandOutput = Artisan::output();
            $this->addOutput($commandOutput);

            if ($exitCode === 0) {
                $this->addOutput('✅ Update completed successfully!');
                Log::info('Update completed successfully via admin panel');

                return [
                    'success' => true,
                    'message' => 'Update completed successfully',
                    'output' => $this->output,
                ];
            }
            $this->addOutput('❌ Update failed with exit code: ' . $exitCode);
            Log::error('Update failed via admin panel', ['exit_code' => $exitCode]);
            return [
                'success' => false,
                'message' => 'Update failed',
                'output' => $this->output,
            ];
        } catch (\Exception $e) {
            $this->addOutput('❌ Update failed with exception: ' . $e->getMessage());

            $errorDetails = UpdateErrorHandler::handleComposerError($e->getMessage(), 1);

            Log::error('Update failed via admin panel', [
                'error' => $e->getMessage(),
                'error_type' => $errorDetails['type'],
            ]);

            return [
                'success' => false,
                'message' => $errorDetails['message'],
                'troubleshooting' => $errorDetails['troubleshooting'],
                'output' => $this->output,
            ];
        } finally {
            $this->isRunning = false;
        }
    }

    public function checkComposerAvailability(): array
    {
        try {
            // $whichProcess = new Process(['which', 'composer']);
            // $whichProcess->setEnv(['PATH' => '/usr/local/bin:/opt/homebrew/bin:/usr/bin:' . getenv('PATH')]);
            // $whichProcess->run();
            // if ($whichProcess->isSuccessful()) {
            //     $composerPath = trim($whichProcess->getOutput());

            //     $process = new Process([$composerPath, '--version']);
            //     $process->setTimeout(10);
            //     $process->setWorkingDirectory(base_path());
            //     $process->run();

            //     if ($process->isSuccessful()) {
            //         return [
            //             'available' => true,
            //             'version' => trim($process->getOutput()),
            //             'message' => 'Composer found at: ' . $composerPath
            //         ];
            //     }
            // }

            $process = new Process(['composer', '--version']);
            $process->setTimeout(10);
            $process->setEnv(['PATH' => '/usr/local/bin:/opt/homebrew/bin:/usr/bin:' . getenv('PATH')]);
            $process->setWorkingDirectory(base_path());
            $process->run();
            if ($process->isSuccessful()) {
                return [
                    'available' => true,
                    'version' => trim($process->getOutput()),
                    'message' => 'Composer is available',
                ];
            }
            return [
                'available' => false,
                'version' => null,
                'message' => 'Composer command failed: ' . $process->getErrorOutput(),
            ];
        } catch (\Exception $e) {
            return [
                'available' => false,
                'version' => null,
                'message' => 'Composer not found: ' . $e->getMessage(),
            ];
        }
    }

    public function validateUpdateEnvironment(): array
    {
        $issues = [];

        // Check if Composer is available
        $composerCheck = $this->checkComposerAvailability();
        if (! $composerCheck['available']) {
            $issues[] = 'Composer is not available: ' . $composerCheck['message'];
        }

        // Check write permissions
        $paths = [
            base_path('composer.json'),
            base_path('composer.lock'),
            base_path('vendor'),
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && ! is_writable($path)) {
                $issues[] = "Path is not writable: {$path}";
            }
        }

        // Check if we're in a git repository (might cause issues)
        if (is_dir(base_path('.git'))) {
            $issues[] = 'Warning: Git repository detected. Composer updates might conflict with version control.';
        }

        return [
            'valid' => $issues === [],
            'issues' => $issues,
        ];
    }

    protected function addOutput(string $message): void
    {
        $this->output[] = [
            'timestamp' => now()->toISOString(),
            'message' => $message,
        ];
    }

    public function getOutput(): array
    {
        return $this->output;
    }

    public function isRunning(): bool
    {
        return $this->isRunning;
    }
}

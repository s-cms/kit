<?php

namespace SmartCms\Kit\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class AssetUpdater
{
    protected array $output = [];

    public function updateAssets(): array
    {
        $this->output = [];

        try {
            // Check if package.json exists
            if (! file_exists(base_path('package.json'))) {
                return [
                    'success' => false,
                    'message' => 'No package.json found. This project does not appear to use npm.',
                    'output' => [],
                ];
            }

            $this->addOutput('Starting frontend assets update...');

            // Step 1: npm ci (clean install)
            if (! $this->runNpmCi()) {
                return [
                    'success' => false,
                    'message' => 'Failed to install npm dependencies',
                    'output' => $this->output,
                ];
            }

            // Step 2: npm run build
            if (! $this->runNpmBuild()) {
                return [
                    'success' => false,
                    'message' => 'Failed to build frontend assets',
                    'output' => $this->output,
                ];
            }

            $this->addOutput('✅ Frontend assets updated successfully!');
            Log::info('Frontend assets updated successfully via admin panel');

            return [
                'success' => true,
                'message' => 'Frontend assets updated successfully',
                'output' => $this->output,
            ];
        } catch (\Exception $e) {
            $this->addOutput('❌ Asset update failed: ' . $e->getMessage());
            Log::error('Asset update failed via admin panel', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Asset update failed: ' . $e->getMessage(),
                'output' => $this->output,
            ];
        }
    }

    protected function runNpmCi(): bool
    {
        $this->addOutput('📦 Installing npm dependencies...');

        $process = new Process(['npm', 'ci'], base_path());
        $process->setEnv($this->getNpmEnv());
        $process->setTimeout(300); // 5 minutes timeout

        $process->run(function ($type, $buffer): void {
            $this->addOutput(trim($buffer));
        });

        if (! $process->isSuccessful()) {
            $this->addOutput('❌ npm ci failed!');
            $this->addOutput('Error: ' . $process->getErrorOutput());

            return false;
        }

        $this->addOutput('✅ npm dependencies installed successfully');

        return true;
    }

    protected function runNpmBuild(): bool
    {
        $this->addOutput('🔨 Building frontend assets...');

        $process = new Process(['npm', 'run', 'build'], base_path());
        $process->setEnv($this->getNpmEnv());
        $process->setTimeout(600); // 10 minutes timeout for build

        $process->run(function ($type, $buffer): void {
            $this->addOutput(trim($buffer));
        });

        if (! $process->isSuccessful()) {
            $this->addOutput('❌ npm run build failed!');
            $this->addOutput('Error: ' . $process->getErrorOutput());

            return false;
        }

        $this->addOutput('✅ Frontend assets built successfully');

        return true;
    }

    public function checkNpmAvailability(): array
    {
        try {
            $process = new Process(['npm', '--version']);
            $process->setEnv($this->getNpmEnv());
            $process->setTimeout(10);
            $process->run();

            if ($process->isSuccessful()) {
                return [
                    'available' => true,
                    'version' => trim($process->getOutput()),
                    'message' => 'npm is available',
                ];
            }
            return [
                'available' => false,
                'version' => null,
                'message' => 'npm command failed',
            ];
        } catch (\Exception $e) {
            return [
                'available' => false,
                'version' => null,
                'message' => 'npm not found: ' . $e->getMessage(),
            ];
        }
    }

    public function validateAssetEnvironment(): array
    {
        $issues = [];

        // Check if npm is available
        $npmCheck = $this->checkNpmAvailability();
        if (! $npmCheck['available']) {
            $issues[] = 'npm is not available: ' . $npmCheck['message'];
        }

        // Check if package.json exists
        if (! file_exists(base_path('package.json'))) {
            $issues[] = 'package.json not found in project root';
        }

        // Check if node_modules directory is writable
        $nodeModulesPath = base_path('node_modules');
        if (file_exists($nodeModulesPath) && ! is_writable($nodeModulesPath)) {
            $issues[] = 'node_modules directory is not writable';
        }

        // Check if public directory is writable (for built assets)
        if (! is_writable(public_path())) {
            $issues[] = 'public directory is not writable';
        }

        return [
            'valid' => $issues === [],
            'issues' => $issues,
        ];
    }

    protected function addOutput(string $message): void
    {
        if (trim($message) !== '' && trim($message) !== '0') {
            $this->output[] = [
                'timestamp' => now()->toISOString(),
                'message' => $message,
            ];
        }
    }

    public function getOutput(): array
    {
        return $this->output;
    }

    public function getNpmEnv(): array
    {
        $npmPath = $this->findExecutable('npm');
        if ($npmPath === null || $npmPath === '' || $npmPath === '0') {
            return [];
        }
        // Update command with full path
        $command[0] = $npmPath;

        $homeDir = getenv('HOME') ?: '/tmp';
        $npmConfigCache = storage_path('npm-cache');

        if (! file_exists($npmConfigCache)) {
            mkdir($npmConfigCache, 0755, true);
        }

        return [
            'PATH' => dirname($npmPath) . ':' . getenv('PATH'),
            'HOME' => $homeDir,
            'npm_config_cache' => $npmConfigCache,
            'NODE_ENV' => app()->environment('production') ? 'production' : 'development',
            'CI' => 'true',
            'npm_config_progress' => 'false',
            'npm_config_fund' => 'false',
            'npm_config_audit' => 'false',
        ];
    }

    private function findExecutable(string $executable): ?string
    {
        $homeDir = getenv('HOME') ?: '/home/' . get_current_user();

        $paths = [
            '/usr/local/bin/' . $executable,
            '/opt/homebrew/bin/' . $executable,
            '/usr/bin/' . $executable,
            '/bin/npm',

            // Snap packages (Ubuntu/other distros)
            '/snap/bin/npm',
            '/snap/node/current/bin/npm',
            '/var/lib/snapd/snap/bin/npm',

            // Flatpak
            '/var/lib/flatpak/exports/bin/npm',
            $homeDir . '/.local/share/flatpak/exports/bin/npm',

            // AppImage locations
            $homeDir . '/Applications/npm',
            '/opt/npm',

            // Distribution-specific paths

            // Debian/Ubuntu
            '/usr/share/nodejs/npm/bin/npm',
            '/usr/lib/nodejs/npm/bin/npm',

            // CentOS/RHEL/Fedora
            '/usr/local/lib/nodejs/npm/bin/npm',
            '/opt/rh/rh-nodejs*/root/usr/bin/npm', // Red Hat Software Collections

            // SUSE
            '/usr/local/nodejs/bin/npm',

            // Arch Linux
            '/usr/share/nodejs/npm/bin/npm',

            // Node Version Managers

            // NVM (Node Version Manager)
            $homeDir . '/.nvm/versions/node/*/bin/npm',
            $homeDir . '/.nvm/current/bin/npm',

            // N (Node version manager)
            '/usr/local/n/versions/node/*/bin/npm',
            $homeDir . '/n/bin/npm',

            // FNM (Fast Node Manager)
            $homeDir . '/.fnm/node-versions/*/installation/bin/npm',
            $homeDir . '/.local/share/fnm/node-versions/*/installation/bin/npm',

            // Volta
            $homeDir . '/.volta/bin/npm',
            $homeDir . '/.volta/tools/image/node/*/bin/npm',

            // ASDF
            $homeDir . '/.asdf/installs/nodejs/*/bin/npm',
            $homeDir . '/.asdf/shims/npm',

            // Manual installations
            '/opt/node/bin/npm',
            '/opt/nodejs/bin/npm',
            $homeDir . '/node/bin/npm',
            $homeDir . '/nodejs/bin/npm',
            $homeDir . '/.local/bin/npm',

            // Docker/Container common mounts
            '/usr/src/app/node_modules/.bin/npm',
            '/app/node_modules/.bin/npm',

            // CI/CD specific paths
            '/github/workspace/node_modules/.bin/npm', // GitHub Actions
            '/builds/node_modules/.bin/npm', // GitLab CI
        ];

        // Also try NVM paths if available
        $homeDir = getenv('HOME');
        if ($homeDir) {
            $nvmPath = $homeDir . '/.nvm/versions/node';
            if (is_dir($nvmPath)) {
                $nodeVersions = glob($nvmPath . '/*/bin/' . $executable);
                $paths = array_merge($paths, $nodeVersions);
            }
        }

        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }
}

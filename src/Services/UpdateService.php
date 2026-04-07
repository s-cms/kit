<?php

namespace SmartCms\Kit\Services;

use Composer\InstalledVersions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SmartCms\Kit\Contracts\UpdateServiceInterface;

class UpdateService implements UpdateServiceInterface
{
    protected string $packageName = 'smart-cms/kit';

    protected string $githubRepository;

    protected int $cacheDuration;

    protected int $timeout;

    public function __construct()
    {
        $this->githubRepository = config('kit.updates.github_repository', 'smart-cms/kit');
        $this->cacheDuration = config('kit.updates.cache_duration', 3600);
        $this->timeout = config('kit.updates.timeout', 30);
    }

    public function getCurrentVersion(): string
    {
        try {
            return InstalledVersions::getPrettyVersion($this->packageName) ?? 'unknown';
        } catch (\Exception $e) {
            Log::warning('Failed to get current version', ['error' => $e->getMessage()]);

            return 'unknown';
        }
    }

    public function getLatestVersion(): ?string
    {
        $cacheKey = 'kit_latest_version';

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($cacheKey) {
            try {
                $response = $this->makeGithubApiCall('/releases/latest');

                if ($response && $response->successful()) {
                    $release = $response->json();

                    return $release['tag_name'] ?? null;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch latest version from GitHub', ['error' => $e->getMessage()]);

                // For rate limiting, we might want to extend cache duration
                if (str_contains($e->getMessage(), 'rate limit')) {
                    // Cache the failure for a shorter time to retry sooner
                    Cache::put($cacheKey . '_error', $e->getMessage(), 300); // 5 minutes
                }
            }

            return null;
        });
    }

    public function getVersionInfo(string $version): ?array
    {
        $cacheKey = "kit_version_info_{$version}";

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($version) {
            try {
                $response = $this->makeGithubApiCall("/releases/tags/{$version}");

                if ($response && $response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch version info from GitHub', [
                    'version' => $version,
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        });
    }

    public function hasUpdatesAvailable(): bool
    {
        $current = $this->getCurrentVersion();
        $latest = $this->getLatestVersion();

        if ($latest === null || $latest === '' || $latest === '0' || $current === 'unknown') {
            return false;
        }

        return version_compare($this->normalizeVersion($current), $this->normalizeVersion($latest), '<');
    }

    public function getUpdateDetails(): ?array
    {
        $current = $this->getCurrentVersion();
        $latest = $this->getLatestVersion();

        if ($latest === null || $latest === '' || $latest === '0') {
            return null;
        }

        $hasUpdates = $this->hasUpdatesAvailable();
        $releaseInfo = null;

        if ($hasUpdates) {
            $releaseInfo = $this->getVersionInfo($latest);
        }

        return [
            'current_version' => $current,
            'latest_version' => $latest,
            'has_updates' => $hasUpdates,
            'release_info' => $releaseInfo,
            'checked_at' => now()->toISOString(),
        ];
    }

    protected function makeGithubApiCall(string $endpoint): ?Response
    {
        $url = "https://api.github.com/repos/{$this->githubRepository}{$endpoint}";

        try {
            $response = Http::timeout($this->timeout)
                ->retry(3, 1000) // Retry 3 times with 1 second delay
                ->withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'SmartCMS-Kit-Updater',
                ])
                ->get($url);

            // Check for rate limiting
            if ($response->status() === 403) {
                $rateLimitRemaining = $response->header('X-RateLimit-Remaining');
                $rateLimitReset = $response->header('X-RateLimit-Reset');

                if ($rateLimitRemaining === '0') {
                    $resetTime = $rateLimitReset ? date('Y-m-d H:i:s', $rateLimitReset) : 'unknown';
                    Log::warning('GitHub API rate limit exceeded', [
                        'url' => $url,
                        'reset_time' => $resetTime,
                    ]);

                    throw new \Exception("GitHub API rate limit exceeded. Resets at: {$resetTime}");
                }
            }

            // Check for other HTTP errors
            if (! $response->successful()) {
                Log::warning('GitHub API returned error status', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $errorMessage = $this->getGithubErrorMessage($response);

                throw new \Exception($errorMessage);
            }

            return $response;
        } catch (ConnectionException $e) {
            Log::warning('GitHub API connection failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Unable to connect to GitHub API. Please check your internet connection.', $e->getCode(), $e);
        } catch (RequestException $e) {
            Log::warning('GitHub API request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('GitHub API request failed: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'rate limit')) {
                throw $e; // Re-throw rate limit exceptions as-is
            }

            Log::warning('GitHub API call failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to fetch update information from GitHub: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function getGithubErrorMessage(Response $response): string
    {
        $status = $response->status();

        switch ($status) {
            case 404:
                return 'Repository not found. Please check the repository configuration.';
            case 403:
                return 'Access denied to GitHub repository. This might be due to rate limiting or repository permissions.';
            case 500:
            case 502:
            case 503:
                return 'GitHub API is currently unavailable. Please try again later.';
            default:
                $body = $response->json();
                $message = $body['message'] ?? 'Unknown error';

                return "GitHub API error ({$status}): {$message}";
        }
    }

    protected function normalizeVersion(string $version): string
    {
        // Remove 'v' prefix if present
        $version = ltrim($version, 'v');

        // Handle dev versions and other suffixes
        if (str_contains($version, '-')) {
            return explode('-', $version)[0];
        }

        return $version;
    }
}

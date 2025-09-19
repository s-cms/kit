<?php

namespace SmartCms\Kit\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use SmartCms\Kit\Contracts\UpdateCheckerInterface;
use SmartCms\Kit\Contracts\UpdateServiceInterface;

class UpdateChecker implements UpdateCheckerInterface
{
    protected string $cacheKey = 'kit_update_notifications';

    protected int $cacheDuration;

    public function __construct(protected UpdateServiceInterface $updateService)
    {
        $this->cacheDuration = config('kit.updates.cache_duration', 3600);
    }

    public function checkOnLogin(): void
    {
        if (! $this->shouldCheck()) {
            return;
        }

        try {
            $retryHandler = new UpdateRetryHandler;

            $updateDetails = $retryHandler->executeWithRetry(
                fn (): ?array => $this->updateService->getUpdateDetails(),
                'github_update_check'
            );

            if ($updateDetails && $updateDetails['has_updates']) {
                $this->storeUpdateNotification($updateDetails);

                Log::info('Update available', [
                    'current_version' => $updateDetails['current_version'],
                    'latest_version' => $updateDetails['latest_version'],
                ]);
            } else {
                // Clear notifications if no updates are available
                $this->clearUpdateNotifications();
            }
        } catch (\Exception $e) {
            // Handle the error gracefully
            $errorDetails = UpdateErrorHandler::handleGithubError($e);

            Log::warning('Failed to check for updates on login', [
                'error' => $e->getMessage(),
                'error_type' => $errorDetails['type'],
            ]);

            // Store error information for display if needed
            if ($errorDetails['type'] === 'rate_limit') {
                Cache::put('kit_update_check_error', $errorDetails, 300); // 5 minutes
            }
        }
    }

    public function shouldCheck(): bool
    {
        // Check if updates are enabled in configuration
        if (! config('kit.updates.enabled', true)) {
            return false;
        }

        // Check frequency setting
        $frequency = config('kit.updates.check_frequency', 'login');

        if ($frequency === 'disabled') {
            return false;
        }

        // For 'login' frequency, always check
        if ($frequency === 'login') {
            return true;
        }

        // For 'daily' frequency, check if we haven't checked today
        if ($frequency === 'daily') {
            $lastCheck = Cache::get('kit_last_update_check');

            return ! $lastCheck || now()->diffInDays($lastCheck) >= 1;
        }

        return false;
    }

    public function storeUpdateNotification(array $updateInfo): void
    {
        Cache::put($this->cacheKey, $updateInfo, $this->cacheDuration);

        // Store last check time for daily frequency
        Cache::put('kit_last_update_check', now(), $this->cacheDuration);
    }

    public function getUpdateNotifications(): ?array
    {
        return Cache::get($this->cacheKey);
    }

    public function clearUpdateNotifications(): void
    {
        Cache::forget($this->cacheKey);
    }
}

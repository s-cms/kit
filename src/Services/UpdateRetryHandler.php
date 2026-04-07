<?php

namespace SmartCms\Kit\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateRetryHandler
{
    // milliseconds
    protected string $cachePrefix = 'kit_update_retry_';

    public function __construct(protected int $maxRetries = 3, protected int $baseDelay = 1000) {}

    public function executeWithRetry(callable $operation, string $operationId): mixed
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $result = $operation();

                // Clear retry count on success
                $this->clearRetryCount($operationId);

                return $result;

            } catch (\Exception $e) {
                $attempt++;
                $lastException = $e;

                Log::warning("Operation failed, attempt {$attempt}/{$this->maxRetries}", [
                    'operation_id' => $operationId,
                    'error' => $e->getMessage(),
                ]);

                // Store retry count
                $this->incrementRetryCount($operationId);

                if ($attempt < $this->maxRetries) {
                    $delay = $this->calculateDelay($attempt);
                    usleep($delay * 1000); // Convert to microseconds
                }
            }
        }

        // All retries failed
        Log::error("Operation failed after {$this->maxRetries} attempts", [
            'operation_id' => $operationId,
            'final_error' => $lastException->getMessage(),
        ]);

        throw $lastException;
    }

    public function shouldRetry(string $operationId, \Exception $e): bool
    {
        // Don't retry certain types of errors
        $nonRetryableErrors = [
            'rate limit',
            'permission denied',
            'composer not found',
            'dependency conflict',
        ];

        $errorMessage = strtolower($e->getMessage());

        foreach ($nonRetryableErrors as $nonRetryable) {
            if (str_contains($errorMessage, $nonRetryable)) {
                return false;
            }
        }

        // Check if we've exceeded retry limits for this operation
        $retryCount = $this->getRetryCount($operationId);

        return $retryCount < $this->maxRetries;
    }

    public function getRetryCount(string $operationId): int
    {
        return Cache::get($this->cachePrefix . $operationId, 0);
    }

    public function incrementRetryCount(string $operationId): void
    {
        $key = $this->cachePrefix . $operationId;
        $count = Cache::get($key, 0) + 1;

        // Store for 1 hour
        Cache::put($key, $count, 3600);
    }

    public function clearRetryCount(string $operationId): void
    {
        Cache::forget($this->cachePrefix . $operationId);
    }

    protected function calculateDelay(int $attempt): int
    {
        // Exponential backoff: base_delay * 2^(attempt-1)
        return $this->baseDelay * 2 ** ($attempt - 1);
    }

    public function getNextRetryTime(string $operationId): ?Carbon
    {
        $retryCount = $this->getRetryCount($operationId);

        if ($retryCount >= $this->maxRetries) {
            return null;
        }

        $delay = $this->calculateDelay($retryCount + 1);

        return now()->addMilliseconds($delay);
    }

    public function canRetryNow(string $operationId): bool
    {
        $nextRetryTime = $this->getNextRetryTime($operationId);

        if (! $nextRetryTime instanceof Carbon) {
            return false;
        }

        return now()->gte($nextRetryTime);
    }
}

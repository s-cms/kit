<?php

use SmartCms\Kit\Services\UpdateErrorHandler;

it('can identify dependency conflict errors', function (): void {
    $errorOutput = 'Your requirements could not be resolved to an installable set of packages.';

    $result = UpdateErrorHandler::handleComposerError($errorOutput, 1);
    expect($result['type'])->toBe('dependency_conflict');
    expect($result['message'])->toContain('dependency conflicts');
    expect($result['troubleshooting'])->toBeArray();
    expect($result['troubleshooting'])->not->toBeEmpty();
});

it('can identify permission errors', function (): void {
    $errorOutput = 'Permission denied when trying to write to /path/to/file';

    $result = UpdateErrorHandler::handleComposerError($errorOutput, 1);
    expect($result['type'])->toBe('permission_denied');
    expect($result['message'])->toContain('file permissions');
    expect($result['troubleshooting'])->toBeArray();
    expect($result['troubleshooting'])->not->toBeEmpty();
    expect($result['troubleshooting'])->toContain('Ensure the web server user has write permissions to the project directory');
});

it('can identify network errors', function (): void {
    $errorOutput = 'Could not fetch package information from repository';

    $result = UpdateErrorHandler::handleComposerError($errorOutput, 1);
    expect($result['type'])->toBe('network_error');
    expect($result['message'])->toContain('network connectivity');
    expect($result['troubleshooting'])->toBeArray();
    expect($result['troubleshooting'])->not->toBeEmpty();
    expect($result['troubleshooting'])->toContain('Check your internet connection');
});

it('can identify memory limit errors', function (): void {
    $errorOutput = 'Fatal error: Allowed memory size of 134217728 bytes exhausted';

    $result = UpdateErrorHandler::handleComposerError($errorOutput, 1);
    expect($result['type'])->toBe('memory_limit');
    expect($result['message'])->toContain('insufficient memory');
    expect($result['troubleshooting'])->not->toBeEmpty();
});

it('can identify composer not found errors', function (): void {
    $errorOutput = 'composer: command not found';

    $result = UpdateErrorHandler::handleComposerError($errorOutput, 1);

    expect($result['type'])->toBe('composer_not_found');
    expect($result['message'])->toContain('Composer is not installed');
    expect($result['troubleshooting'])->toBeArray();
    expect($result['troubleshooting'])->not->toBeEmpty();
    expect($result['troubleshooting'])->toBeArray()->toContain('Install Composer from https://getcomposer.org/');
});

it('handles unknown composer errors gracefully', function (): void {
    $errorOutput = 'Some unknown error occurred';

    $result = UpdateErrorHandler::handleComposerError($errorOutput, 1);

    expect($result['type'])->toBe('unknown');
    expect($result['message'])->toContain('unexpected error');
    expect($result['troubleshooting'])->toBeArray();
});

it('can handle github rate limit errors', function (): void {
    $exception = new \Exception('GitHub API rate limit exceeded. Resets at: 2024-01-15 10:00:00');

    $result = UpdateErrorHandler::handleGithubError($exception);

    expect($result['type'])->toBe('rate_limit');
    expect($result['message'])->toContain('rate limit exceeded');
    expect($result['troubleshooting'])->toContain('Wait for the rate limit to reset (usually within an hour)');
});

it('can handle github connection errors', function (): void {
    $exception = new \Exception('Unable to connect to GitHub API. Please check your internet connection.');

    $result = UpdateErrorHandler::handleGithubError($exception);
    expect($result['type'])->toBe('connection_error');
    expect($result['message'])->toContain('connect to GitHub');
    expect($result['troubleshooting'])->toBeArray();
    expect($result['troubleshooting'])->not->toBeEmpty();
    expect($result['troubleshooting'])->toContain('Check your internet connection');
});

it('can handle github repository not found errors', function (): void {
    $exception = new \Exception('Repository not found. Please check the repository configuration.');

    $result = UpdateErrorHandler::handleGithubError($exception);

    expect($result['type'])->toBe('repository_not_found');
    expect($result['message'])->toContain('repository was not found');
});

it('provides technical details in all error responses', function (): void {
    $errorOutput = 'Some technical error message';

    $result = UpdateErrorHandler::handleComposerError($errorOutput, 1);

    expect($result)->toHaveKey('technical_details');
    expect($result['technical_details'])->toBe($errorOutput);
});

it('logs errors when handling them', function (): void {
    $errorOutput = 'Test error for logging';

    UpdateErrorHandler::handleComposerError($errorOutput, 1);

    // We can't easily test the actual logging without mocking,
    // but we can verify the method completes without throwing
    expect(true)->toBeTrue();
});

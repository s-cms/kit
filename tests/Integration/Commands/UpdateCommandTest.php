<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use SmartCms\Kit\Contracts\UpdateServiceInterface;

beforeEach(function (): void {
    Config::set('kit.updates.enabled', true);
});

it('can check for updates without installing', function (): void {
    $mockUpdateService = Mockery::mock(UpdateServiceInterface::class);
    $mockUpdateService->shouldReceive('getCurrentVersion')->andReturn('1.0.0');
    $mockUpdateService->shouldReceive('getLatestVersion')->andReturn('1.1.0');
    $mockUpdateService->shouldReceive('hasUpdatesAvailable')->andReturn(true);

    $this->app->instance(UpdateServiceInterface::class, $mockUpdateService);

    $exitCode = Artisan::call('scms:update', ['--check' => true]);

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('Update Available');
});

it('shows up to date message when no updates available', function (): void {
    $mockUpdateService = Mockery::mock(UpdateServiceInterface::class);
    $mockUpdateService->shouldReceive('getCurrentVersion')->andReturn('1.0.0');
    $mockUpdateService->shouldReceive('getLatestVersion')->andReturn('1.0.0');
    $mockUpdateService->shouldReceive('hasUpdatesAvailable')->andReturn(false);

    $this->app->instance(UpdateServiceInterface::class, $mockUpdateService);

    $exitCode = Artisan::call('scms:update', ['--check' => true]);

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('up to date');
});

it('handles update service errors gracefully during check', function (): void {
    $mockUpdateService = Mockery::mock(UpdateServiceInterface::class);
    $mockUpdateService->shouldReceive('getCurrentVersion')->andThrow(new Exception('Service error'));

    $this->app->instance(UpdateServiceInterface::class, $mockUpdateService);

    $exitCode = Artisan::call('scms:update', ['--check' => true]);

    expect($exitCode)->toBe(1);
    expect(Artisan::output())->toContain('Failed to check for updates');
});

it('can force update even when no updates detected', function (): void {
    $mockUpdateService = Mockery::mock(UpdateServiceInterface::class);
    $mockUpdateService->shouldReceive('hasUpdatesAvailable')->andReturn(false);
    $mockUpdateService->shouldReceive('getCurrentVersion')->andReturn('1.0.0');

    $this->app->instance(UpdateServiceInterface::class, $mockUpdateService);

    // Mock the composer command to avoid actual execution
    $this->artisan('scms:update', ['--force' => true])
        ->expectsOutput('Starting Smart CMS Kit update...')
        ->assertExitCode(1);
});

it('shows no updates message when not forced', function (): void {
    $mockUpdateService = Mockery::mock(UpdateServiceInterface::class);
    $mockUpdateService->shouldReceive('hasUpdatesAvailable')->andReturn(false);

    $this->app->instance(UpdateServiceInterface::class, $mockUpdateService);

    $exitCode = Artisan::call('scms:update');

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('No updates available');
});

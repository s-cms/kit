<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use SmartCms\Kit\Contracts\UpdateServiceInterface;
use SmartCms\Kit\Services\UpdateChecker;

beforeEach(function (): void {
    Cache::flush();

    $this->mockUpdateService = Mockery::mock(UpdateServiceInterface::class);
    $this->updateChecker = new UpdateChecker($this->mockUpdateService);
});

it('should check for updates when enabled and frequency is login', function (): void {
    Config::set('kit.updates.enabled', true);
    Config::set('kit.updates.check_frequency', 'login');

    expect($this->updateChecker->shouldCheck())->toBeTrue();
});

it('should not check when updates are disabled', function (): void {
    Config::set('kit.updates.enabled', false);

    expect($this->updateChecker->shouldCheck())->toBeFalse();
});

it('should not check when frequency is disabled', function (): void {
    Config::set('kit.updates.enabled', true);
    Config::set('kit.updates.check_frequency', 'disabled');

    expect($this->updateChecker->shouldCheck())->toBeFalse();
});

it('should check daily when frequency is daily and no previous check', function (): void {
    Config::set('kit.updates.enabled', true);
    Config::set('kit.updates.check_frequency', 'daily');

    expect($this->updateChecker->shouldCheck())->toBeTrue();
});

it('should not check daily when already checked today', function (): void {
    Config::set('kit.updates.enabled', true);
    Config::set('kit.updates.check_frequency', 'daily');

    Cache::put('kit_last_update_check', now(), 3600);

    expect($this->updateChecker->shouldCheck())->toBeFalse();
});

it('can store and retrieve update notifications', function (): void {
    $updateInfo = [
        'current_version' => '1.0.0',
        'latest_version' => '1.1.0',
        'has_updates' => true,
        'release_info' => ['tag_name' => 'v1.1.0'],
    ];

    $this->updateChecker->storeUpdateNotification($updateInfo);

    $stored = $this->updateChecker->getUpdateNotifications();

    expect($stored)->toBe($updateInfo);
});

it('can clear update notifications', function (): void {
    $updateInfo = [
        'current_version' => '1.0.0',
        'latest_version' => '1.1.0',
        'has_updates' => true,
    ];

    $this->updateChecker->storeUpdateNotification($updateInfo);
    $this->updateChecker->clearUpdateNotifications();

    expect($this->updateChecker->getUpdateNotifications())->toBeNull();
});

it('checks for updates on login when updates are available', function (): void {
    Config::set('kit.updates.enabled', true);
    Config::set('kit.updates.check_frequency', 'login');

    $updateDetails = [
        'current_version' => '1.0.0',
        'latest_version' => '1.1.0',
        'has_updates' => true,
        'release_info' => ['tag_name' => 'v1.1.0'],
    ];

    $this->mockUpdateService
        ->shouldReceive('getUpdateDetails')
        ->once()
        ->andReturn($updateDetails);

    $this->updateChecker->checkOnLogin();

    $stored = $this->updateChecker->getUpdateNotifications();
    expect($stored)->toBe($updateDetails);
});

it('clears notifications when no updates are available', function (): void {
    Config::set('kit.updates.enabled', true);
    Config::set('kit.updates.check_frequency', 'login');

    // First store some old notification
    $this->updateChecker->storeUpdateNotification(['has_updates' => true]);

    $updateDetails = [
        'current_version' => '1.0.0',
        'latest_version' => '1.0.0',
        'has_updates' => false,
    ];

    $this->mockUpdateService
        ->shouldReceive('getUpdateDetails')
        ->once()
        ->andReturn($updateDetails);

    $this->updateChecker->checkOnLogin();

    expect($this->updateChecker->getUpdateNotifications())->toBeNull();
});

it('does not check when shouldCheck returns false', function (): void {
    Config::set('kit.updates.enabled', false);

    $this->mockUpdateService
        ->shouldNotReceive('getUpdateDetails');

    $this->updateChecker->checkOnLogin();
});

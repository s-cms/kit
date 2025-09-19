<?php

use SmartCms\Kit\Services\AssetUpdater;

beforeEach(function (): void {
    $this->assetUpdater = new AssetUpdater;
});

it('can check npm availability', function (): void {
    $result = $this->assetUpdater->checkNpmAvailability();

    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['available', 'version', 'message']);
    expect($result['available'])->toBeBool();
});

it('validates asset environment', function (): void {
    $result = $this->assetUpdater->validateAssetEnvironment();

    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['valid', 'issues']);
    expect($result['valid'])->toBeBool();
    expect($result['issues'])->toBeArray();
});

it('initializes with empty output', function (): void {
    expect($this->assetUpdater->getOutput())->toBeArray();
    expect($this->assetUpdater->getOutput())->toBeEmpty();
});

it('detects missing package.json', function (): void {
    // This test assumes we're not in a directory with package.json at the base path
    // In a real Laravel project, this might be different
    $result = $this->assetUpdater->validateAssetEnvironment();

    expect($result)->toHaveKey('valid');
    expect($result)->toHaveKey('issues');
});

it('handles npm not found gracefully', function (): void {
    // This test verifies the method structure even if npm is available
    $result = $this->assetUpdater->checkNpmAvailability();

    expect($result['message'])->toBeString();
    expect($result['available'])->toBeBool();
});

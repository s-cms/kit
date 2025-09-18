<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SmartCms\Kit\Services\UpdateService;

beforeEach(function () {
    $this->updateService = new UpdateService;
    Cache::flush();
});

it('can get current version', function () {
    $version = $this->updateService->getCurrentVersion();

    expect($version)->toBeString();
    expect($version)->not->toBeEmpty();
});

// it('can fetch latest version from github', function () {
//     Http::fake([
//         'api.github.com/repos/smart-cms/kit/releases/latest' => Http::response([
//             'tag_name' => 'v1.2.3',
//             'name' => 'Version 1.2.3',
//             'body' => 'Release notes...',
//         ], 200),
//     ]);

//     $version = $this->updateService->getLatestVersion();

//     expect($version)->toBe('v1.2.3');
// });

// it('returns null when github api fails', function () {
//     Http::fake([
//         'api.github.com/repos/smart-cms/kit/releases/latest' => Http::response([], 404),
//     ]);

//     $version = $this->updateService->getLatestVersion();

//     expect($version)->toBeNull();
// });

// it('can get version info for specific version', function () {
//     Http::fake([
//         'api.github.com/repos/smart-cms/kit/releases/tags/v1.2.3' => Http::response([
//             'tag_name' => 'v1.2.3',
//             'name' => 'Version 1.2.3',
//             'body' => 'Release notes for version 1.2.3',
//             'published_at' => '2024-01-15T10:00:00Z',
//         ], 200),
//     ]);

//     $info = $this->updateService->getVersionInfo('v1.2.3');

//     expect($info)->toBeArray();
//     expect($info['tag_name'])->toBe('v1.2.3');
//     expect($info['body'])->toBe('Release notes for version 1.2.3');
// });

// it('can detect when updates are available', function () {
//     Http::fake([
//         'api.github.com/repos/smart-cms/kit/releases/latest' => Http::response([
//             'tag_name' => 'v999.0.0', // Much higher version
//         ], 200),
//     ]);

//     $hasUpdates = $this->updateService->hasUpdatesAvailable();

//     expect($hasUpdates)->toBeTrue();
// });

// it('can detect when no updates are available', function () {
//     Http::fake([
//         'api.github.com/repos/smart-cms/kit/releases/latest' => Http::response([
//             'tag_name' => 'v0.0.0', // Much lower version
//         ], 200),
//     ]);

//     $hasUpdates = $this->updateService->hasUpdatesAvailable();
//     expect($hasUpdates)->toBeFalse();
// });

// it('can get complete update details', function () {
//     Http::fake([
//         'api.github.com/repos/smart-cms/kit/releases/latest' => Http::response([
//             'tag_name' => 'v999.0.0',
//         ], 200),
//         'api.github.com/repos/smart-cms/kit/releases/tags/v999.0.0' => Http::response([
//             'tag_name' => 'v999.0.0',
//             'name' => 'Version 999.0.0',
//             'body' => 'Major update with new features',
//             'published_at' => '2024-01-15T10:00:00Z',
//         ], 200),
//     ]);

//     $details = $this->updateService->getUpdateDetails();

//     expect($details)->toBeArray();
//     expect($details['current_version'])->toBeString();
//     expect($details['latest_version'])->toBe('v999.0.0');
//     expect($details['has_updates'])->toBeTrue();
//     expect($details['release_info'])->toBeArray();
//     expect($details['checked_at'])->toBeString();
// });

// it('caches github api responses', function () {
//     Http::fake([
//         'api.github.com/repos/smart-cms/kit/releases/latest' => Http::response([
//             'tag_name' => 'v1.2.3',
//         ], 200),
//     ]);

//     // First call
//     $version1 = $this->updateService->getLatestVersion();

//     // Second call should use cache
//     $version2 = $this->updateService->getLatestVersion();

//     expect($version1)->toBe($version2);

//     // Should only have made one HTTP request
//     Http::assertSentCount(1);
// });

it('normalizes versions correctly', function () {
    $service = new class extends UpdateService
    {
        public function testNormalizeVersion(string $version): string
        {
            return $this->normalizeVersion($version);
        }
    };

    expect($service->testNormalizeVersion('v1.2.3'))->toBe('1.2.3');
    expect($service->testNormalizeVersion('1.2.3-dev'))->toBe('1.2.3');
    expect($service->testNormalizeVersion('v1.2.3-beta'))->toBe('1.2.3');
});

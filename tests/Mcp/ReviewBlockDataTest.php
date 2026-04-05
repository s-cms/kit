<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use SmartCms\Kit\Mcp\Tools\ReviewBlockData;
use SmartCms\Kit\Models\Block;
use SmartCms\Lang\Database\Factories\LanguageFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    LanguageFactory::new()->create([
        'slug' => 'en',
        'name' => 'English',
    ]);
    LanguageFactory::new()->create([
        'slug' => 'uk',
        'name' => 'Ukrainian',
    ]);
});

/**
 * Decode MCP response JSON
 */
function decodeReviewResponse($response): array
{
    $content = $response->content[0]['text'] ?? $response->content()->__toString() ?? '{}';

    return json_decode($content, true) ?? [];
}

it('can review block data with transformed values', function () {
    $block = Block::factory()->create([
        'type' => 'TestBlock',
        'title' => 'Test Block',
        'status' => true,
        'data' => [
            'en' => ['title' => 'English Title'],
            'uk' => ['title' => 'Ukrainian Title'],
        ],
    ]);

    $tool = app(ReviewBlockData::class);
    $response = $tool->handle(new Request([
        'id' => $block->id,
    ]));

    $result = decodeReviewResponse($response);

    expect($result)->toHaveKey('id', $block->id);
    expect($result)->toHaveKey('type', 'TestBlock');
    expect($result)->toHaveKey('title', 'Test Block');
    expect($result)->toHaveKey('status', true);
    expect($result)->toHaveKey('schema');
    expect($result)->toHaveKey('transformed_data');
    expect($result['transformed_data'])->toBeArray();
});

it('can review block data for specific language', function () {
    $block = Block::factory()->create([
        'data' => [
            'en' => ['title' => 'English Title'],
            'uk' => ['title' => 'Ukrainian Title'],
        ],
    ]);

    $tool = app(ReviewBlockData::class);
    $response = $tool->handle(new Request([
        'id' => $block->id,
        'language' => 'uk',
    ]));

    $result = decodeReviewResponse($response);

    expect($result)->toHaveKey('transformed_data');
    expect($result['transformed_data']['title'])->toBe('Ukrainian Title');
});

it('can review block data including raw data', function () {
    $block = Block::factory()->create([
        'data' => [
            'en' => ['title' => 'English Title'],
        ],
    ]);

    $tool = app(ReviewBlockData::class);
    $response = $tool->handle(new Request([
        'id' => $block->id,
        'language' => 'en',
        'include_raw' => true,
    ]));

    $result = decodeReviewResponse($response);

    expect($result)->toHaveKey('raw_data');
    expect($result)->toHaveKey('transformed_data');
    expect($result['raw_data'])->toBe(['title' => 'English Title']);
});

it('can review block data for all locales', function () {
    $block = Block::factory()->create([
        'data' => [
            'en' => ['title' => 'English Title'],
            'uk' => ['title' => 'Ukrainian Title'],
        ],
    ]);

    $tool = app(ReviewBlockData::class);
    $response = $tool->handle(new Request([
        'id' => $block->id,
    ]));

    $result = decodeReviewResponse($response);

    expect($result['transformed_data'])->toBeArray();
    expect($result['transformed_data'])->toHaveKey('en');
    expect($result['transformed_data'])->toHaveKey('uk');
});

it('returns block metadata along with transformed data', function () {
    $block = Block::factory()->create([
        'type' => 'HeaderBlock',
        'title' => 'Site Header',
        'status' => true,
        'data' => [
            'en' => ['logo' => 'logo.png'],
        ],
    ]);

    $tool = app(ReviewBlockData::class);
    $response = $tool->handle(new Request([
        'id' => $block->id,
        'language' => 'en',
    ]));

    $result = decodeReviewResponse($response);

    expect($result['id'])->toBe($block->id);
    expect($result['type'])->toBe('HeaderBlock');
    expect($result['title'])->toBe('Site Header');
    expect($result['status'])->toBeTrue();
    expect($result)->toHaveKey('created_at');
    expect($result)->toHaveKey('updated_at');
    expect($result)->toHaveKey('schema');
});

it('fails to review non-existent block', function () {
    $tool = app(ReviewBlockData::class);

    expect(function () {
        $tool->handle(new Request([
            'id' => 999999,
        ]));
    })->toThrow(Exception::class);
});

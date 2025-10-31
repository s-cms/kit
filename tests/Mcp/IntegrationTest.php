<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use SmartCms\Kit\Mcp\Tools\CreateBlockInstance;
use SmartCms\Kit\Mcp\Tools\CreatePage;
use SmartCms\Kit\Mcp\Tools\GetPageBlocks;
use SmartCms\Kit\Mcp\Tools\UpdateBlockData;
use SmartCms\Kit\Mcp\Tools\UpdatePageSeo;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Services\Block\BlockService;
use SmartCms\Lang\Database\Factories\LanguageFactory;

use function Pest\Laravel\assertDatabaseHas;

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
function decodeIntegrationResponse($response): array
{
    $content = $response->content[0]['text'] ?? $response->content()->__toString() ?? '{}';

    return json_decode($content, true) ?? [];
}

it('can create page with seo workflow', function () {
    // 1. Create page
    $createTool = app(CreatePage::class);
    $createResponse = $createTool->handle(new Request([
        'name' => 'Integration Test Page',
        'title' => 'Integration Test Page',
        'slug' => 'integration-test',
        'status' => 'draft',
    ]));

    $createResult = decodeIntegrationResponse($createResponse);
    expect($createResult)->toHaveKey('id');
    $pageId = $createResult['id'];

    // 2. Update SEO
    $seoTool = app(UpdatePageSeo::class);
    $seoResponse = $seoTool->handle(new Request([
        'id' => $pageId,
        'meta_title' => 'Integration Test SEO Title',
        'meta_description' => 'This is a test description for integration testing',
    ]));
    expect($seoResponse->content()->__toString())->toBeString()
        ->toContain('success');
});

it('can get page blocks with data', function () {
    $page = Page::factory()->create();
    $block = Block::factory()->create([
        'data' => [
            'en' => ['title' => 'English Block Title'],
            'uk' => ['title' => 'Ukrainian Block Title'],
        ],
    ]);

    $page->blocks()->attach($block->id);

    $tool = app(GetPageBlocks::class);
    $response = $tool->handle(new Request([
        'id' => $page->id,
        'language' => 'en',
    ]));

    $result = decodeIntegrationResponse($response);

    expect($result)->toBeArray()
        ->toHaveCount(1);
});

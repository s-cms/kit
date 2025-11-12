<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use SmartCms\Kit\Mcp\Tools\CreatePage;
use SmartCms\Kit\Mcp\Tools\DeletePage;
use SmartCms\Kit\Mcp\Tools\GetPage;
use SmartCms\Kit\Mcp\Tools\GetPages;
use SmartCms\Kit\Mcp\Tools\PublishPage;
use SmartCms\Kit\Mcp\Tools\UnpublishPage;
use SmartCms\Kit\Mcp\Tools\UpdatePage;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;
use SmartCms\Lang\Database\Factories\LanguageFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    LanguageFactory::new()->create([
        'slug' => 'en',
    ]);
});
/**
 * Decode MCP response JSON
 */
function decodePageResponse($response): array
{
    $content = $response->content[0]['text'] ?? $response->content()->__toString() ?? '{}';

    return json_decode($content, true) ?? [];
}

it('can list pages', function () {
    // Create test pages
    Page::factory()->count(3)->create();

    $tool = app(GetPages::class);
    $response = $tool->handle(new Request);

    $result = decodePageResponse($response);
    expect(count($result))->toBe(3);
});

it('can filter pages by status', function () {
    Page::factory()->create(['name' => 'published-page', 'slug' => 'published-page', 'status' => PageStatus::Published->value]);
    Page::factory()->create(['name' => 'draft-page', 'slug' => 'draft-page', 'status' => PageStatus::Draft->value]);

    $tool = app(GetPages::class);
    $response = $tool->handle(new Request(['status' => PageStatus::Published->value]));

    $result = decodePageResponse($response);
    expect(count($result))->toBe(1);
    expect($result[0]['status'])->toBe(PageStatus::Published->value);
});

it('can search pages', function () {
    Page::factory()->create(['name' => 'About Us']);
    Page::factory()->create(['name' => 'Contact']);

    $tool = app(GetPages::class);
    $response = $tool->handle(new Request(['search' => 'About']));

    $result = decodePageResponse($response);
    expect(count($result))->toBe(1);
    expect($result[0]['name'])->toContain('About');
});

it('can get page by id', function () {
    $page = Page::factory()->create(['name' => 'Test Page']);

    $tool = app(GetPage::class);
    $response = $tool->handle(new Request(['id' => $page->id]));

    $result = decodePageResponse($response);

    expect($result['id'])->toBe($page->id);
    expect($result['name'])->toBe('Test Page');
});

it('can get page by slug', function () {
    $page = Page::factory()->create(['slug' => 'test-page']);

    $tool = app(GetPage::class);
    $response = $tool->handle(new Request(['slug' => 'test-page']));

    $result = decodePageResponse($response);

    expect($result['id'])->toBe($page->id);
    expect($result['slug'])->toBe('test-page');
});

it('fails to get page without id or slug', function () {
    $tool = app(GetPage::class);
    $response = $tool->handle(new Request);
    expect($response->content()->__toString())->toBe('Either id or slug must be provided');
});

it('can create page', function () {
    $tool = app(CreatePage::class);
    $response = $tool->handle(new Request([
        'name' => 'New Page',
        'slug' => 'new-page',
        'status' => PageStatus::Draft->value,
    ]));

    $result = decodePageResponse($response);
    expect($result['id'])->toBeGreaterThan(0);
    expect($result['name'])->toBe('New Page');
    expect($result['slug'])->toBe('new-page');
    expect($result['status'])->toBe(PageStatus::Draft->value);
});

it('can update page', function () {
    $page = Page::factory()->create(['name' => 'Old Title']);

    $tool = app(UpdatePage::class);
    $response = $tool->handle(new Request([
        'id' => $page->id,
        'name' => 'New Title',
    ]));

    $result = decodePageResponse($response);
    expect($result['name'])->toBe('New Title');
});

it('can publish page', function () {
    $page = Page::factory()->create(['status' => PageStatus::Draft->value]);

    $tool = app(PublishPage::class);
    $response = $tool->handle(new Request(['id' => $page->id]));
    expect($response->content()->__toString())->toContain('published successfully');
});

it('can unpublish page', function () {
    $page = Page::factory()->create(['status' => true]);

    $tool = app(UnpublishPage::class);
    $response = $tool->handle(new Request(['id' => $page->id]));
    expect($response->content()->__toString())->toContain('unpublished successfully');
});

it('can delete page', function () {
    $page = Page::factory()->create();

    $tool = app(DeletePage::class);
    $response = $tool->handle(new Request(['id' => $page->id]));
    expect($response->content()->__toString())->toContain('deleted successfully');
    $page = Page::find($page->id);
    expect($page)->toBeNull();
});

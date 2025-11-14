<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use SmartCms\Kit\Http\Handlers\PageHandler;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;
use SmartCms\Lang\Database\Factories\LanguageFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    LanguageFactory::new()->create(['slug' => 'en']);

    // Mock the lang helper
    if (! function_exists('current_lang')) {
        function current_lang()
        {
            return 'en';
        }
    }
});

it('finds root page by slug', function () {
    $page = Page::factory()->create([
        'slug' => 'about',
        'status' => PageStatus::Published->value,
    ]);

    $handler = new PageHandler;
    $request = Request::create('/about');

    $result = $handler->__invoke($request, 'about');

    expect($result)->toBeString();
});

it('finds nested page by hierarchical path', function () {
    $parent = Page::factory()->create([
        'slug' => 'parent',
        'status' => PageStatus::Published->value,
        'type' => 'category',
    ]);

    $child = Page::factory()->create([
        'slug' => 'child',
        'parent_id' => $parent->id,
        'status' => PageStatus::Published->value,
    ]);

    $handler = new PageHandler;
    $request = Request::create('/parent/child');

    $result = $handler->__invoke($request, 'parent/child');

    expect($result)->toBeString();
});

it('finds deeply nested pages', function () {
    $level1 = Page::factory()->create([
        'slug' => 'level1',
        'status' => PageStatus::Published->value,
        'type' => 'category',
    ]);

    $level2 = Page::factory()->create([
        'slug' => 'level2',
        'parent_id' => $level1->id,
        'status' => PageStatus::Published->value,
        'type' => 'category',
    ]);

    $level3 = Page::factory()->create([
        'slug' => 'level3',
        'parent_id' => $level2->id,
        'status' => PageStatus::Published->value,
    ]);

    $handler = new PageHandler;
    $request = Request::create('/level1/level2/level3');

    $result = $handler->__invoke($request, 'level1/level2/level3');

    expect($result)->toBeString();
});

it('returns 404 for non-existent page', function () {
    $handler = new PageHandler;
    $request = Request::create('/non-existent-page');

    expect(fn () => $handler->__invoke($request, 'non-existent-page'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});

it('returns 404 for non-existent nested page', function () {
    $parent = Page::factory()->create([
        'slug' => 'parent',
        'status' => PageStatus::Published->value,
        'type' => 'category',
    ]);

    $handler = new PageHandler;
    $request = Request::create('/parent/non-existent');

    expect(fn () => $handler->__invoke($request, 'parent/non-existent'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});

it('validates max depth', function () {
    config(['kit.max_page_depth' => 2]);

    $handler = new PageHandler;
    $request = Request::create('/level1/level2/level3/level4');

    expect(fn () => $handler->__invoke($request, 'level1/level2/level3/level4'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});

it('handles empty path segments correctly', function () {
    $page = Page::factory()->create([
        'slug' => 'test',
        'status' => PageStatus::Published->value,
    ]);

    $handler = new PageHandler;
    $request = Request::create('/test');

    $result = $handler->__invoke($request, 'test');

    expect($result)->toBeString();
});

it('returns 404 when parent exists but child does not', function () {
    $parent = Page::factory()->create([
        'slug' => 'parent',
        'status' => PageStatus::Published->value,
        'type' => 'category',
    ]);

    $handler = new PageHandler;
    $request = Request::create('/parent/missing-child');

    expect(fn () => $handler->__invoke($request, 'parent/missing-child'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});

it('handles root path with empty slug', function () {
    $page = Page::factory()->create([
        'slug' => '',
        'status' => PageStatus::Published->value,
    ]);

    $handler = new PageHandler;
    $request = Request::create('/');

    $result = $handler->__invoke($request, '');

    expect($result)->toBeString();
});

it('respects max page depth from config', function () {
    config(['kit.max_page_depth' => 3]);

    $level1 = Page::factory()->create([
        'slug' => 'l1',
        'status' => PageStatus::Published->value,
        'type' => 'category',
    ]);

    $level2 = Page::factory()->create([
        'slug' => 'l2',
        'parent_id' => $level1->id,
        'status' => PageStatus::Published->value,
        'type' => 'category',
    ]);

    $level3 = Page::factory()->create([
        'slug' => 'l3',
        'parent_id' => $level2->id,
        'status' => PageStatus::Published->value,
    ]);

    $handler = new PageHandler;
    $request = Request::create('/l1/l2/l3');

    $result = $handler->__invoke($request, 'l1/l2/l3');

    expect($result)->toBeString();

    // Level 4 should be rejected
    $request2 = Request::create('/l1/l2/l3/l4');

    expect(fn () => $handler->__invoke($request2, 'l1/l2/l3/l4'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});

it('uses default max depth when not configured', function () {
    config()->forget('kit.max_page_depth');

    $handler = new PageHandler;

    // Create a page at depth 5 (the default max)
    $pages = [];
    $parentId = null;

    for ($i = 1; $i <= 5; $i++) {
        $page = Page::factory()->create([
            'slug' => "level{$i}",
            'parent_id' => $parentId,
            'status' => PageStatus::Published->value,
            'type' => $i < 5 ? 'category' : 'page',
        ]);
        $pages[] = $page;
        $parentId = $page->id;
    }

    $path = implode('/', array_map(fn ($p) => $p->slug, $pages));
    $request = Request::create('/' . $path);

    $result = $handler->__invoke($request, $path);

    expect($result)->toBeString();
});

it('correctly identifies wrong parent in path', function () {
    $parent1 = Page::factory()->create([
        'slug' => 'parent1',
        'status' => PageStatus::Published->value,
        'type' => 'category',
    ]);

    $parent2 = Page::factory()->create([
        'slug' => 'parent2',
        'status' => PageStatus::Published->value,
        'type' => 'category',
    ]);

    $child = Page::factory()->create([
        'slug' => 'child',
        'parent_id' => $parent2->id,
        'status' => PageStatus::Published->value,
    ]);

    $handler = new PageHandler;
    $request = Request::create('/parent1/child');

    // Should not find child under wrong parent
    expect(fn () => $handler->__invoke($request, 'parent1/child'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});

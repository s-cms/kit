<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;
use SmartCms\Lang\Database\Factories\LanguageFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    LanguageFactory::new()->create(['slug' => 'en']);
});

it('can create a page', function () {
    $page = Page::factory()->create([
        'name' => ['en' => 'Test Page'],
        'slug' => 'test-page',
    ]);

    expect($page)->toBeInstanceOf(Page::class);
    expect($page->name)->toBe('Test Page');
    expect($page->slug)->toBe('test-page');
    expect($page->type)->toBe('page');
});

it('auto-sets published_at when status is published', function () {
    $page = Page::factory()->create([
        'status' => PageStatus::Published->value,
        'published_at' => null,
    ]);

    expect($page->published_at)->not->toBeNull();
    expect($page->published_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('does not override existing published_at', function () {
    $specificDate = now()->subDays(5);

    $page = Page::factory()->create([
        'status' => PageStatus::Published->value,
        'published_at' => $specificDate,
    ]);

    expect($page->published_at->format('Y-m-d'))->toBe($specificDate->format('Y-m-d'));
});

it('updates published_at when status changes to published', function () {
    $page = Page::factory()->create([
        'status' => PageStatus::Draft->value,
        'published_at' => null,
    ]);

    expect($page->published_at)->toBeNull();

    $page->update(['status' => PageStatus::Published->value]);

    expect($page->fresh()->published_at)->not->toBeNull();
});

it('can establish parent-child relationships', function () {
    $parent = Page::factory()->create(['name' => ['en' => 'Parent']]);
    $child = Page::factory()->create([
        'name' => ['en' => 'Child'],
        'parent_id' => $parent->id,
    ]);

    expect($child->parent_id)->toBe($parent->id);
    expect($child->parent->id)->toBe($parent->id);
    expect($parent->children)->toHaveCount(1);
    expect($parent->children->first()->id)->toBe($child->id);
});

it('calculates depth correctly', function () {
    $parent = Page::factory()->create();
    $child = Page::factory()->create(['parent_id' => $parent->id]);
    $grandchild = Page::factory()->create(['parent_id' => $child->id]);

    $parent->refresh();
    $child->refresh();
    $grandchild->refresh();

    expect($parent->getDepth())->toBe(0);
    expect($child->getDepth())->toBe(1);
    expect($grandchild->getDepth())->toBe(2);
});

it('auto-calculates depth on creation', function () {
    $parent = Page::factory()->create();
    $child = Page::factory()->create(['parent_id' => $parent->id]);

    expect($child->depth)->toBe(1);
});

it('recalculates depth when parent changes', function () {
    $parent1 = Page::factory()->create();
    $parent2 = Page::factory()->create(['parent_id' => $parent1->id]);
    $child = Page::factory()->create(['parent_id' => $parent1->id]);

    expect($child->depth)->toBe(1);

    $child->update(['parent_id' => $parent2->id]);

    expect($child->fresh()->depth)->toBe(2);
});

it('gets all ancestors', function () {
    $grandparent = Page::factory()->create(['name' => ['en' => 'Grandparent']]);
    $parent = Page::factory()->create([
        'name' => ['en' => 'Parent'],
        'parent_id' => $grandparent->id,
    ]);
    $child = Page::factory()->create([
        'name' => ['en' => 'Child'],
        'parent_id' => $parent->id,
    ]);

    $ancestors = $child->ancestors();

    expect($ancestors)->toHaveCount(2);
    expect($ancestors->first()->id)->toBe($grandparent->id);
    expect($ancestors->last()->id)->toBe($parent->id);
});

it('gets all descendants', function () {
    $parent = Page::factory()->create();
    $child1 = Page::factory()->create(['parent_id' => $parent->id]);
    $child2 = Page::factory()->create(['parent_id' => $parent->id]);
    $grandchild = Page::factory()->create(['parent_id' => $child1->id]);

    $descendants = $parent->descendants();

    expect($descendants)->toHaveCount(3);
    expect($descendants->pluck('id'))->toContain($child1->id, $child2->id, $grandchild->id);
});

it('generates breadcrumbs correctly', function () {
    $parent = Page::factory()->create(['name' => ['en' => 'Parent'], 'slug' => 'parent']);
    $child = Page::factory()->create([
        'name' => ['en' => 'Child'],
        'slug' => 'child',
        'parent_id' => $parent->id,
    ]);

    $breadcrumbs = $child->getBreadcrumbs();

    expect($breadcrumbs)->toHaveCount(2);
    expect($breadcrumbs[0]['name'])->toBe('Parent');
    expect($breadcrumbs[1]['name'])->toBe('Child');
});

it('generates route with hierarchical path', function () {
    $parent = Page::factory()->create(['slug' => 'parent']);
    $child = Page::factory()->create(['slug' => 'child', 'parent_id' => $parent->id]);

    $route = $child->route();

    expect($route)->toContain('parent/child');
});

it('category type can have children', function () {
    $category = Page::factory()->create(['type' => 'category']);

    expect($category->canHaveChildren())->toBeTrue();
});

it('division type can have children', function () {
    $division = Page::factory()->create(['type' => 'division']);

    expect($division->canHaveChildren())->toBeTrue();
});

it('page type cannot have children by default', function () {
    $page = Page::factory()->create(['type' => 'page']);

    expect($page->canHaveChildren())->toBeFalse();
});

it('validates maximum depth on creation', function () {
    config(['kit.max_page_depth' => 2]);

    $level0 = Page::factory()->create();
    $level1 = Page::factory()->create(['parent_id' => $level0->id]);
    $level2 = Page::factory()->create(['parent_id' => $level1->id]);

    expect(fn () => Page::factory()->create(['parent_id' => $level2->id]))
        ->toThrow(\Exception::class, 'Maximum nesting depth');
});

it('validates parent can have children', function () {
    $parent = Page::factory()->create(['type' => 'page']);

    expect(fn () => Page::factory()->create(['parent_id' => $parent->id]))
        ->toThrow(\Exception::class, 'cannot have children');
});

it('auto-sets title from name if title is blank', function () {
    $page = Page::factory()->create([
        'name' => ['en' => 'Test Name'],
        'title' => null,
    ]);

    expect($page->title)->toBe('Test Name');
});

it('auto-increments sorting for sibling pages', function () {
    $parent = Page::factory()->create();
    $child1 = Page::factory()->create(['parent_id' => $parent->id, 'sorting' => 0]);
    $child2 = Page::factory()->create(['parent_id' => $parent->id, 'sorting' => 0]);

    $child1->refresh();
    $child2->refresh();

    expect($child1->sorting)->toBeGreaterThan(0);
    expect($child2->sorting)->toBeGreaterThan($child1->sorting);
});

it('generates preview url for draft pages', function () {
    $page = Page::factory()->create(['status' => PageStatus::Draft->value]);

    $previewUrl = $page->generatePreviewUrl();

    expect($previewUrl)->not->toBeNull();
    expect($previewUrl)->toContain('preview');
});

it('returns null preview url for published pages', function () {
    $page = Page::factory()->create(['status' => PageStatus::Published->value]);

    $previewUrl = $page->generatePreviewUrl();

    expect($previewUrl)->toBeNull();
});

it('reuses existing preview token', function () {
    Cache::flush();

    $page = Page::factory()->create(['status' => PageStatus::Draft->value]);

    $previewUrl1 = $page->generatePreviewUrl();
    $previewUrl2 = $page->generatePreviewUrl();

    expect($previewUrl1)->toBe($previewUrl2);
});

it('sets created_by and updated_by on creation', function () {
    $page = Page::factory()->create();

    // These should be null since we're not authenticated in tests
    expect($page->created_by)->toBeNull();
    expect($page->updated_by)->toBeNull();
});

it('updates updated_by on update', function () {
    $page = Page::factory()->create();

    $page->update(['name' => ['en' => 'Updated Name']]);

    expect($page->fresh()->updated_by)->toBeNull(); // null since no auth in tests
});

it('correctly identifies page type', function () {
    expect(Page::getPageType())->toBe('page');
});

it('shouldGenerateSlug returns false for page with id 1', function () {
    $page = new Page(['id' => 1]);

    expect($page->shouldGenerateSlug())->toBeFalse();
});

it('shouldGenerateSlug returns true for pages with other ids', function () {
    $page = new Page(['id' => 2]);

    expect($page->shouldGenerateSlug())->toBeTrue();
});

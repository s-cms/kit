<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use SmartCms\Kit\Casts\PageStatusCast;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Support\Contracts\PageStatus;
use SmartCms\Lang\Database\Factories\LanguageFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    LanguageFactory::new()->create(['slug' => 'en']);
});

it('returns draft status when value is null', function () {
    $cast = new PageStatusCast();
    $model = new Page();

    $result = $cast->get($model, 'status', null, []);

    expect($result)->toBe(PageStatus::Draft->value);
});

it('returns draft status when value is false', function () {
    $cast = new PageStatusCast();
    $model = new Page();

    $result = $cast->get($model, 'status', false, []);

    expect($result)->toBe(PageStatus::Draft->value);
});

it('returns draft status when value is empty string', function () {
    $cast = new PageStatusCast();
    $model = new Page();

    $result = $cast->get($model, 'status', '', []);

    expect($result)->toBe(PageStatus::Draft->value);
});

it('returns actual value when value is truthy', function () {
    $cast = new PageStatusCast();
    $model = new Page();

    $result = $cast->get($model, 'status', PageStatus::Published->value, []);

    expect($result)->toBe(PageStatus::Published->value);
});

it('returns published status for existing value', function () {
    $cast = new PageStatusCast();
    $model = new Page();

    $result = $cast->get($model, 'status', 'published', []);

    expect($result)->toBe('published');
});

it('sets published status for home page (id = 1)', function () {
    $cast = new PageStatusCast();
    $model = new Page();

    $result = $cast->set($model, 'status', PageStatus::Draft->value, ['id' => 1]);

    expect($result)->toBe(PageStatus::Published->value);
});

it('does not override status for non-home pages', function () {
    $cast = new PageStatusCast();
    $model = new Page();

    $result = $cast->set($model, 'status', PageStatus::Draft->value, ['id' => 2]);

    expect($result)->toBe(PageStatus::Draft->value);
});

it('handles PageStatus enum instance', function () {
    $cast = new PageStatusCast();
    $model = new Page();

    $result = $cast->set($model, 'status', PageStatus::Published, ['id' => 2]);

    expect($result)->toBe(PageStatus::Published->value);
});

it('handles string status value', function () {
    $cast = new PageStatusCast();
    $model = new Page();

    $result = $cast->set($model, 'status', 'draft', ['id' => 2]);

    expect($result)->toBe('draft');
});

it('forces published status on home page regardless of input', function () {
    $cast = new PageStatusCast();
    $model = new Page();

    // Try to set draft on home page
    $result = $cast->set($model, 'status', PageStatus::Draft->value, ['id' => 1]);

    expect($result)->toBe(PageStatus::Published->value);
});

it('page status enum has correct values', function () {
    expect(PageStatus::Draft->value)->toBe('draft');
    expect(PageStatus::Published->value)->toBe('published');
    expect(PageStatus::Scheduled->value)->toBe('scheduled');
});

it('page status enum has correct colors', function () {
    expect(PageStatus::Draft->getColor())->toBe('gray');
    expect(PageStatus::Scheduled->getColor())->toBe('info');
    expect(PageStatus::Published->getColor())->toBe('success');
});

it('page status enum has labels', function () {
    expect(PageStatus::Draft->getLabel())->toBeString();
    expect(PageStatus::Published->getLabel())->toBeString();
    expect(PageStatus::Scheduled->getLabel())->toBeString();
});

it('integrates with page model correctly', function () {
    $page = Page::factory()->create([
        'status' => PageStatus::Published->value,
    ]);

    expect($page->status)->toBeInstanceOf(PageStatus::class);
    expect($page->status)->toBe(PageStatus::Published);
});

it('defaults to draft for new pages', function () {
    $page = new Page([
        'name' => ['en' => 'Test'],
        'slug' => 'test',
    ]);

    // Status should default to draft via cast
    $cast = new PageStatusCast();
    $result = $cast->get($page, 'status', null, []);

    expect($result)->toBe(PageStatus::Draft->value);
});

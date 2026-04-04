<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use SmartCms\Kit\Actions\Support\RenameTranslationKey;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Models\Page;
use SmartCms\Lang\Models\Language;

beforeEach(function (): void {
    // Override translatable_models to avoid Tag compatibility issue in tests
    config()->set('kit.translatable_models', []);

    Language::create([
        'name' => 'English',
        'slug' => 'en',
        'locale' => 'en',
        'is_default' => true,
        'is_admin_active' => true,
        'is_frontend_active' => true,
    ]);
    Language::create([
        'name' => 'Ukrainian',
        'slug' => 'uk',
        'locale' => 'uk',
        'is_default' => false,
        'is_admin_active' => false,
        'is_frontend_active' => false,
    ]);
});

it('renames translation key in page translatable fields', function (): void {
    $page = Page::create([
        'name' => ['en' => 'Home'],
        'slug' => 'home',
        'title' => ['en' => 'Welcome'],
        'description' => ['en' => 'Site description'],
        'status' => 'published',
        'sorting' => 1,
    ]);

    $action = new RenameTranslationKey;
    $updated = $action->handle('en', 'uk');

    $page->refresh();

    expect($page->getTranslation('name', 'uk'))->toBe('Home')
        ->and($page->getTranslation('title', 'uk'))->toBe('Welcome')
        ->and($page->getTranslation('description', 'uk'))->toBe('Site description')
        ->and($page->getTranslations('name'))->not->toHaveKey('en')
        ->and($updated)->toBeGreaterThanOrEqual(1);
});

it('renames translation key in block data', function (): void {
    $block = Block::create([
        'title' => 'Test Block',
        'type' => 'test-block',
        'schema' => [],
        'data' => ['en' => ['title' => 'Block Title', 'content' => '<p>Hello</p>']],
        'status' => true,
    ]);

    $action = new RenameTranslationKey;
    $action->handle('en', 'uk');

    $block->refresh();

    expect($block->getTranslation('data', 'uk'))->toBe(['title' => 'Block Title', 'content' => '<p>Hello</p>'])
        ->and($block->getTranslations('data'))->not->toHaveKey('en');
});

it('does not overwrite existing translation key', function (): void {
    $page = Page::create([
        'name' => ['en' => 'Home', 'uk' => 'Головна'],
        'slug' => 'home',
        'title' => ['en' => 'Welcome', 'uk' => 'Ласкаво просимо'],
        'status' => 'published',
        'sorting' => 1,
    ]);

    $action = new RenameTranslationKey;
    $action->handle('en', 'uk');

    $page->refresh();

    expect($page->getTranslation('name', 'uk'))->toBe('Головна')
        ->and($page->getTranslation('title', 'uk'))->toBe('Ласкаво просимо');
});

it('handles pages with empty translatable fields', function (): void {
    $page = Page::create([
        'name' => ['en' => 'Home'],
        'slug' => 'home',
        'title' => null,
        'description' => null,
        'status' => 'published',
        'sorting' => 1,
    ]);

    $action = new RenameTranslationKey;
    $updated = $action->handle('en', 'uk');

    $page->refresh();

    expect($page->getTranslation('name', 'uk'))->toBe('Home')
        ->and($updated)->toBe(1);
});

it('returns zero when no records have the old language key', function (): void {
    Page::create([
        'name' => ['uk' => 'Головна'],
        'slug' => 'home',
        'status' => 'published',
        'sorting' => 1,
    ]);

    $action = new RenameTranslationKey;
    $updated = $action->handle('en', 'uk');

    expect($updated)->toBe(0);
});

it('correctly detects when rename is needed', function (): void {
    Page::create([
        'name' => ['en' => 'Home'],
        'slug' => 'home',
        'status' => 'published',
        'sorting' => 1,
    ]);

    $action = new RenameTranslationKey;

    expect($action->needsRename('en', 'uk'))->toBeTrue();
});

it('detects rename is not needed when new key already exists', function (): void {
    Page::create([
        'name' => ['en' => 'Home', 'uk' => 'Головна'],
        'slug' => 'home',
        'status' => 'published',
        'sorting' => 1,
    ]);

    $action = new RenameTranslationKey;

    expect($action->needsRename('en', 'uk'))->toBeFalse();
});

it('detects rename is not needed when old key does not exist', function (): void {
    Page::create([
        'name' => ['uk' => 'Головна'],
        'slug' => 'home',
        'status' => 'published',
        'sorting' => 1,
    ]);

    $action = new RenameTranslationKey;

    expect($action->needsRename('en', 'uk'))->toBeFalse();
});

it('renames keys across multiple models in a single call', function (): void {
    Page::create([
        'name' => ['en' => 'Home'],
        'slug' => 'home',
        'status' => 'published',
        'sorting' => 1,
    ]);

    Block::create([
        'title' => 'Hero Block',
        'type' => 'hero',
        'schema' => [],
        'data' => ['en' => ['title' => 'Hero']],
        'status' => true,
    ]);

    $action = new RenameTranslationKey;
    $updated = $action->handle('en', 'uk');

    expect($updated)->toBe(2)
        ->and(Page::first()->getTranslation('name', 'uk'))->toBe('Home')
        ->and(Block::first()->getTranslation('data', 'uk'))->toBe(['title' => 'Hero']);
});

it('preserves other language keys when renaming', function (): void {
    $page = Page::create([
        'name' => ['en' => 'Home', 'de' => 'Startseite'],
        'slug' => 'home',
        'status' => 'published',
        'sorting' => 1,
    ]);

    $action = new RenameTranslationKey;
    $action->handle('en', 'uk');

    $page->refresh();

    expect($page->getTranslation('name', 'uk'))->toBe('Home')
        ->and($page->getTranslation('name', 'de'))->toBe('Startseite')
        ->and($page->getTranslations('name'))->not->toHaveKey('en');
});

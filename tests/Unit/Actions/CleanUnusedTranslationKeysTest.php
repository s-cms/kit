<?php

use SmartCms\Kit\Actions\Support\CleanUnusedTranslationKeys;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Models\Page;
use SmartCms\Lang\Models\Language;

beforeEach(function (): void {
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
    Language::create([
        'name' => 'German',
        'slug' => 'de',
        'locale' => 'de',
        'is_default' => false,
        'is_admin_active' => false,
        'is_frontend_active' => false,
    ]);
});

it('removes unused language keys from pages', function (): void {
    $page = Page::create([
        'name' => ['en' => 'Home', 'uk' => 'Головна', 'de' => 'Startseite'],
        'slug' => 'home',
        'title' => ['en' => 'Welcome', 'uk' => 'Ласкаво просимо'],
        'status' => 'published',
        'sorting' => 1,
    ]);

    $action = new CleanUnusedTranslationKeys;
    $updated = $action->handle(['en']);

    $page->refresh();

    expect($page->getTranslations('name'))->toBe(['en' => 'Home'])
        ->and($page->getTranslations('title'))->toBe(['en' => 'Welcome'])
        ->and($updated)->toBe(1);
});

it('removes unused language keys from blocks', function (): void {
    $block = Block::create([
        'title' => 'Hero',
        'type' => 'hero',
        'schema' => [],
        'data' => ['en' => ['title' => 'Hello'], 'de' => ['title' => 'Hallo']],
        'status' => true,
    ]);

    $action = new CleanUnusedTranslationKeys;
    $action->handle(['en']);

    $block->refresh();

    expect($block->getTranslations('data'))->toBe(['en' => ['title' => 'Hello']]);
});

it('keeps multiple active languages', function (): void {
    $page = Page::create([
        'name' => ['en' => 'Home', 'uk' => 'Головна', 'de' => 'Startseite'],
        'slug' => 'home',
        'status' => 'published',
        'sorting' => 1,
    ]);

    $action = new CleanUnusedTranslationKeys;
    $action->handle(['en', 'uk']);

    $page->refresh();

    expect($page->getTranslations('name'))->toBe(['en' => 'Home', 'uk' => 'Головна']);
});

it('returns zero when no unused keys exist', function (): void {
    Page::create([
        'name' => ['en' => 'Home'],
        'slug' => 'home',
        'status' => 'published',
        'sorting' => 1,
    ]);

    $action = new CleanUnusedTranslationKeys;
    $updated = $action->handle(['en']);

    expect($updated)->toBe(0);
});

it('handles empty translatable fields gracefully', function (): void {
    Page::create([
        'name' => ['en' => 'Home', 'de' => 'Startseite'],
        'slug' => 'home',
        'title' => null,
        'description' => null,
        'status' => 'published',
        'sorting' => 1,
    ]);

    $action = new CleanUnusedTranslationKeys;
    $updated = $action->handle(['en']);

    expect($updated)->toBe(1);
});

it('detects unused keys when they exist', function (): void {
    Page::create([
        'name' => ['en' => 'Home', 'de' => 'Startseite'],
        'slug' => 'home',
        'status' => 'published',
        'sorting' => 1,
    ]);

    $action = new CleanUnusedTranslationKeys;

    expect($action->hasUnusedKeys(['en']))->toBeTrue();
});

it('detects no unused keys when all are active', function (): void {
    Page::create([
        'name' => ['en' => 'Home'],
        'slug' => 'home',
        'status' => 'published',
        'sorting' => 1,
    ]);

    $action = new CleanUnusedTranslationKeys;

    expect($action->hasUnusedKeys(['en']))->toBeFalse();
});

it('cleans across multiple models in a single call', function (): void {
    Page::create([
        'name' => ['en' => 'Home', 'de' => 'Startseite'],
        'slug' => 'home',
        'status' => 'published',
        'sorting' => 1,
    ]);

    Block::create([
        'title' => 'Hero',
        'type' => 'hero',
        'schema' => [],
        'data' => ['en' => ['title' => 'Hello'], 'de' => ['title' => 'Hallo']],
        'status' => true,
    ]);

    $action = new CleanUnusedTranslationKeys;
    $updated = $action->handle(['en']);

    expect($updated)->toBe(2)
        ->and(Page::first()->getTranslations('name'))->toBe(['en' => 'Home'])
        ->and(Block::first()->getTranslations('data'))->toBe(['en' => ['title' => 'Hello']]);
});

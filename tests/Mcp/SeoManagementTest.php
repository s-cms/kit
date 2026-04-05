<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use SmartCms\Kit\Mcp\Tools\UpdatePageSeo;
use SmartCms\Kit\Models\Page;
use SmartCms\Lang\Database\Factories\LanguageFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    LanguageFactory::new()->create([
        'slug' => 'en',
    ]);
});

it('can update page seo', function () {
    $page = Page::factory()->create();

    $tool = app(UpdatePageSeo::class);
    $response = $tool->handle(new Request([
        'id' => $page->id,
        'meta_title' => 'SEO Title',
        'meta_description' => 'SEO Description for testing',
        'meta_keywords' => 'test, seo, keywords',
        'language' => 'en',
    ]));

    // Just verify the tool runs without throwing exception
    expect($response)->toBeInstanceOf(Response::class);
    // Verify database - SEO fields are translatable JSON
    $page->refresh();
    expect($page->getTranslation('title', 'en'))->toBe('SEO Title');
    expect($page->getTranslation('description', 'en'))->toBe('SEO Description for testing');
});

it('can update heading and summary', function () {
    $page = Page::factory()->create();

    $tool = app(UpdatePageSeo::class);
    $tool->handle(new Request([
        'id' => $page->id,
        'heading' => 'Page Heading',
        'summary' => 'Page Summary',
        'language' => 'en',
    ]));

    $page->refresh();
    expect($page->getTranslation('heading', 'en'))->toBe('Page Heading');
    expect($page->getTranslation('summary', 'en'))->toBe('Page Summary');
});

it('validates field lengths', function () {
    $page = Page::factory()->create();

    $tool = app(UpdatePageSeo::class);

    expect(fn () => $tool->handle(new Request([
        'id' => $page->id,
        'meta_title' => str_repeat('a', 256), // Exceeds 255 char limit
        'language' => 'en',
    ])))->toThrow(Exception::class);
});

it('can update partial seo fields', function () {
    $page = Page::factory()->create();

    // Set initial values
    $page->setTranslation('title', 'en', 'Original Title');
    $page->setTranslation('description', 'en', 'Original Description');
    $page->save();

    $tool = app(UpdatePageSeo::class);
    $tool->handle(new Request([
        'id' => $page->id,
        'meta_title' => 'Updated Title',
        // Not updating description
        'language' => 'en',
    ]));

    $page->refresh();
    expect($page->getTranslation('title', 'en'))->toBe('Updated Title');
    expect($page->getTranslation('description', 'en'))->toBe('Original Description');
});

it('can update content field', function () {
    $page = Page::factory()->create();

    $tool = app(UpdatePageSeo::class);
    $tool->handle(new Request([
        'id' => $page->id,
        'content' => 'This is the page content with <strong>HTML</strong>',
        'language' => 'en',
    ]));

    $page->refresh();
    expect($page->getTranslation('content', 'en'))->toContain('HTML');
});

it('fails to update seo for nonexistent page', function () {
    $tool = app(UpdatePageSeo::class);

    expect(fn () => $tool->handle(new Request([
        'id' => 999999,
        'meta_title' => 'Test',
        'language' => 'en',
    ])))->toThrow(Exception::class);
});

it('works without language parameter', function () {
    $page = Page::factory()->create();

    $tool = app(UpdatePageSeo::class);
    $response = $tool->handle(new Request([
        'id' => $page->id,
        'meta_title' => 'Title Without Language',
    ]));

    // Should use default language and not throw exception
    expect($response)->toBeInstanceOf(Response::class);
});

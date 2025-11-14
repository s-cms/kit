<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use SmartCms\Kit\Http\Handlers\SitemapHandler;
use SmartCms\Kit\Models\Page;
use SmartCms\Lang\Database\Factories\LanguageFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    LanguageFactory::new()->create(['slug' => 'en']);

    // Register sitemap route
    Route::get('sitemap', [SitemapHandler::class, 'handle'])->name('sitemap');
});

it('returns sitemap index when no lang parameter is provided', function () {
    $handler = new SitemapHandler;

    $response = $handler->handle();

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('text/xml');
});

it('generates sitemap with indexed pages', function () {
    Page::factory()->count(3)->create([
        'is_index' => true,
    ]);

    $handler = new SitemapHandler;
    $response = $handler->handle();
    $response->header('lang', 'en');

    request()->merge(['lang' => 'en']);
    $response = $handler->handle();

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('text/xml');
});

it('excludes non-indexed pages from sitemap', function () {
    $indexedPage = Page::factory()->create([
        'slug' => 'indexed-page',
        'is_index' => true,
    ]);

    $nonIndexedPage = Page::factory()->create([
        'slug' => 'non-indexed-page',
        'is_index' => false,
    ]);

    request()->merge(['lang' => 'en']);
    $handler = new SitemapHandler;
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('indexed-page');
    expect($content)->not->toContain('non-indexed-page');
});

it('includes page priority in sitemap', function () {
    Page::factory()->create([
        'is_index' => true,
    ]);

    request()->merge(['lang' => 'en']);
    $handler = new SitemapHandler;
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('priority');
    expect($content)->toContain('0.7');
});

it('includes changefreq in sitemap', function () {
    Page::factory()->create([
        'is_index' => true,
    ]);

    request()->merge(['lang' => 'en']);
    $handler = new SitemapHandler;
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('changefreq');
    expect($content)->toContain('weekly');
});

it('includes lastmod in sitemap', function () {
    $page = Page::factory()->create([
        'is_index' => true,
        'published_at' => now()->subDays(5),
    ]);

    request()->merge(['lang' => 'en']);
    $handler = new SitemapHandler;
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('lastmod');
});

it('uses published_at for lastmod when available', function () {
    $publishedDate = now()->subDays(10);

    $page = Page::factory()->create([
        'is_index' => true,
        'published_at' => $publishedDate,
        'created_at' => now()->subDays(20),
    ]);

    request()->merge(['lang' => 'en']);
    $handler = new SitemapHandler;
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain($publishedDate->toIso8601String());
});

it('falls back to created_at for lastmod when published_at is null', function () {
    $page = Page::factory()->create([
        'is_index' => true,
        'published_at' => null,
    ]);

    request()->merge(['lang' => 'en']);
    $handler = new SitemapHandler;
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('lastmod');
    expect($content)->toContain($page->created_at->toIso8601String());
});

it('handles empty sitemap gracefully', function () {
    // No pages created

    request()->merge(['lang' => 'en']);
    $handler = new SitemapHandler;
    $response = $handler->handle();

    expect($response->getStatusCode())->toBe(200);
});

it('sets correct content type header', function () {
    $handler = new SitemapHandler;
    $response = $handler->handle();

    expect($response->headers->get('Content-Type'))->toContain('text/xml');
});

it('renders sitemap index without lang parameter', function () {
    $handler = new SitemapHandler;
    $response = $handler->renderSitemap();

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('text/xml');
});

it('switches locale based on lang parameter', function () {
    LanguageFactory::new()->create(['slug' => 'uk']);

    Page::factory()->create(['is_index' => true]);

    $originalLocale = app()->getLocale();

    request()->merge(['lang' => 'uk']);
    $handler = new SitemapHandler;
    $response = $handler->handle();

    expect($response->getStatusCode())->toBe(200);
});

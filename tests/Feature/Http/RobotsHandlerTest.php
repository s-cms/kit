<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use SmartCms\Kit\Http\Handlers\RobotsHandler;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Register sitemap route for robots.txt
    Route::get('sitemap', function () {
        return 'sitemap';
    })->name('sitemap');
});

it('returns robots txt with correct content type', function () {
    $handler = new RobotsHandler();
    $response = $handler->handle();

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('text/plain');
});

it('disallows all when indexation is disabled', function () {
    app('s')->shouldReceive('get')
        ->with('indexation', false)
        ->andReturn(false);

    $handler = new RobotsHandler();
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('User-agent: *');
    expect($content)->toContain('Disallow: /');
    expect($content)->not->toContain('Sitemap:');
});

it('allows indexation when enabled', function () {
    app('s')->shouldReceive('get')
        ->with('indexation', false)
        ->andReturn(true);

    $handler = new RobotsHandler();
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('User-agent: *');
    expect($content)->not->toBe("User-agent: *\nDisallow: /");
});

it('disallows admin routes when indexation is enabled', function () {
    app('s')->shouldReceive('get')
        ->with('indexation', false)
        ->andReturn(true);

    $handler = new RobotsHandler();
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('Disallow: /admin');
});

it('disallows cart routes when indexation is enabled', function () {
    app('s')->shouldReceive('get')
        ->with('indexation', false)
        ->andReturn(true);

    $handler = new RobotsHandler();
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('Disallow: /cart');
});

it('disallows checkout routes when indexation is enabled', function () {
    app('s')->shouldReceive('get')
        ->with('indexation', false)
        ->andReturn(true);

    $handler = new RobotsHandler();
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('Disallow: /checkout');
});

it('disallows search routes when indexation is enabled', function () {
    app('s')->shouldReceive('get')
        ->with('indexation', false)
        ->andReturn(true);

    $handler = new RobotsHandler();
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('Disallow: /search');
});

it('disallows register routes when indexation is enabled', function () {
    app('s')->shouldReceive('get')
        ->with('indexation', false)
        ->andReturn(true);

    $handler = new RobotsHandler();
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('Disallow: /register');
});

it('disallows reset-password routes when indexation is enabled', function () {
    app('s')->shouldReceive('get')
        ->with('indexation', false)
        ->andReturn(true);

    $handler = new RobotsHandler();
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('Disallow: /reset-password');
});

it('disallows pagination routes when indexation is enabled', function () {
    app('s')->shouldReceive('get')
        ->with('indexation', false)
        ->andReturn(true);

    $handler = new RobotsHandler();
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('Disallow: /*page*');
});

it('includes sitemap url when indexation is enabled', function () {
    app('s')->shouldReceive('get')
        ->with('indexation', false)
        ->andReturn(true);

    $handler = new RobotsHandler();
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('Sitemap:');
    expect($content)->toContain(route('sitemap'));
});

it('includes host when indexation is enabled', function () {
    app('s')->shouldReceive('get')
        ->with('indexation', false)
        ->andReturn(true);

    $handler = new RobotsHandler();
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toContain('Host:');
    expect($content)->toContain(request()->getHost());
});

it('returns minimal robots txt when indexation is disabled', function () {
    app('s')->shouldReceive('get')
        ->with('indexation', false)
        ->andReturn(false);

    $handler = new RobotsHandler();
    $response = $handler->handle();

    $content = $response->getContent();

    expect($content)->toBe("User-agent: *\nDisallow: /");
});

it('returns comprehensive robots txt when indexation is enabled', function () {
    app('s')->shouldReceive('get')
        ->with('indexation', false)
        ->andReturn(true);

    $handler = new RobotsHandler();
    $response = $handler->handle();

    $content = $response->getContent();

    $expectedPatterns = [
        'User-agent: *',
        'Disallow: /admin',
        'Disallow: /cart',
        'Disallow: /checkout',
        'Disallow: /search',
        'Disallow: /register',
        'Disallow: /reset-password',
        'Disallow: /*page*',
        'Sitemap:',
        'Host:',
    ];

    foreach ($expectedPatterns as $pattern) {
        expect($content)->toContain($pattern);
    }
});

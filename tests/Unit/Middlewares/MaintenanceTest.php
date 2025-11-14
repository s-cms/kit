<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use SmartCms\Kit\Http\Middlewares\Maintenance;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

it('passes request through when maintenance is disabled', function () {
    app('s')->shouldReceive('get')
        ->with('system.maintenance', false)
        ->andReturn(false);

    $middleware = new Maintenance;
    $request = Request::create('/test');

    $response = $middleware->handle($request, function () {
        return new Response('OK');
    });

    expect($response->getContent())->toBe('OK');
});

it('blocks request when maintenance is enabled', function () {
    app('s')->shouldReceive('get')
        ->with('system.maintenance', false)
        ->andReturn(true);

    $middleware = new Maintenance;
    $request = Request::create('/test');

    expect(fn () => $middleware->handle($request, function () {
        return new Response('OK');
    }))->toThrow(ServiceUnavailableHttpException::class);
});

it('allows bypass with maintenance_bypass cookie', function () {
    app('s')->shouldReceive('get')
        ->with('system.maintenance', false)
        ->andReturn(true);

    $middleware = new Maintenance;
    $request = Request::create('/test');
    $request->cookies->set('maintenance_bypass', 'true');

    $response = $middleware->handle($request, function () {
        return new Response('OK');
    });

    expect($response->getContent())->toBe('OK');
});

it('blocks request without bypass cookie when maintenance is enabled', function () {
    app('s')->shouldReceive('get')
        ->with('system.maintenance', false)
        ->andReturn(true);

    $middleware = new Maintenance;
    $request = Request::create('/test');
    // No bypass cookie

    expect(fn () => $middleware->handle($request, function () {
        return new Response('OK');
    }))->toThrow(ServiceUnavailableHttpException::class);
});

it('returns 503 status code when maintenance mode is active', function () {
    app('s')->shouldReceive('get')
        ->with('system.maintenance', false)
        ->andReturn(true);

    $middleware = new Maintenance;
    $request = Request::create('/test');

    try {
        $middleware->handle($request, function () {
            return new Response('OK');
        });
    } catch (ServiceUnavailableHttpException $e) {
        expect($e->getStatusCode())->toBe(503);
    }
});

it('handles multiple requests correctly', function () {
    app('s')->shouldReceive('get')
        ->with('system.maintenance', false)
        ->andReturn(false);

    $middleware = new Maintenance;

    // First request
    $request1 = Request::create('/test1');
    $response1 = $middleware->handle($request1, function () {
        return new Response('Request 1');
    });

    // Second request
    $request2 = Request::create('/test2');
    $response2 = $middleware->handle($request2, function () {
        return new Response('Request 2');
    });

    expect($response1->getContent())->toBe('Request 1');
    expect($response2->getContent())->toBe('Request 2');
});

it('bypass cookie with any value works', function () {
    app('s')->shouldReceive('get')
        ->with('system.maintenance', false)
        ->andReturn(true);

    $middleware = new Maintenance;
    $request = Request::create('/test');
    $request->cookies->set('maintenance_bypass', 'any_value');

    $response = $middleware->handle($request, function () {
        return new Response('OK');
    });

    expect($response->getContent())->toBe('OK');
});

it('only checks cookie name not value', function () {
    app('s')->shouldReceive('get')
        ->with('system.maintenance', false)
        ->andReturn(true);

    $middleware = new Maintenance;
    $request = Request::create('/test');
    $request->cookies->set('maintenance_bypass', '');

    // Empty cookie value should still bypass maintenance
    // Because we only check if the cookie exists
    $response = $middleware->handle($request, function () {
        return new Response('OK');
    });

    expect($response->getContent())->toBe('OK');
});

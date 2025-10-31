<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use SmartCms\Kit\Mcp\Tools\GetLanguages;
use SmartCms\Kit\Mcp\Tools\GetMenu;
use SmartCms\Kit\Mcp\Tools\GetMenus;
use SmartCms\Lang\Models\Language;
use SmartCms\Menu\Models\Menu;

uses(RefreshDatabase::class);

/**
 * Decode MCP response JSON
 */
function decodeResponse($response): array
{
    $content = $response->content[0]['text'] ?? $response->content()->__toString() ?? '{}';

    return json_decode($content, true);
}

it('can get languages', function () {
    // Mock language service if needed
    if (! app()->has('lang')) {
        $this->markTestSkipped('Language service not available');
    }
    Language::query()->create([
        'slug' => 'en',
        'name' => 'English',
        'locale' => 'en_US',
        'is_default' => true,
        'is_admin_active' => true,
        'is_frontend_active' => true,
    ]);

    $tool = app(GetLanguages::class);
    $response = $tool->handle(new Request());
    $result = decodeResponse($response);
    expect($result)->toHaveKey('main');
    expect($result)->toHaveKey('frontend');
    expect($result)->toHaveKey('admin');
    expect($result['frontend'])->toBeArray();
    expect($result['admin'])->toBeArray();
});

it('can list menus', function () {
    Menu::factory()->count(3)->create();

    $tool = app(GetMenus::class);
    $response = $tool->handle(new Request());

    $result = decodeResponse($response);

    expect($result)->toHaveKey('count');
    expect($result)->toHaveKey('menus');
    expect($result['count'])->toBe(3);

    // Verify menu structure
    foreach ($result['menus'] as $menu) {
        expect($menu)->toHaveKey('id');
        expect($menu)->toHaveKey('name');
        expect($menu)->toHaveKey('items_count');
    }
});

it('can get menu by id', function () {
    $menu = Menu::factory()->create([
        'name' => 'Main Menu',
        'items' => [
            ['title' => 'Home', 'url' => '/'],
            ['title' => 'About', 'url' => '/about'],
        ],
    ]);

    $tool = app(GetMenu::class);
    $response = $tool->handle(new Request(['id' => $menu->id]));

    $result = decodeResponse($response);

    expect($result['id'])->toBe($menu->id);
    expect($result['name'])->toBe('Main Menu');
    expect($result)->toHaveKey('items');
    expect($result['items'])->toHaveCount(2);
    expect($result['items'][0]['title'])->toBe('Home');
});

it('can get menu by name', function () {
    $menu = Menu::factory()->create(['name' => 'Footer Menu']);

    $tool = app(GetMenu::class);
    $response = $tool->handle(new Request(['name' => 'Footer Menu']));

    $result = decodeResponse($response);

    expect($result['id'])->toBe($menu->id);
    expect($result['name'])->toBe('Footer Menu');
});

it('fails to get menu without id or name', function () {
    $tool = app(GetMenu::class);
    $response = $tool->handle(new Request());

    $content = $response->content[0]['text'] ?? $response->content()->__toString() ?? '';

    expect($content)->toContain('id or name');
});

it('fails to get nonexistent menu', function () {
    $tool = app(GetMenu::class);
    $response = $tool->handle(new Request(['id' => 999999]));

    $content = $response->content[0]['text'] ?? $response->content()->__toString() ?? '';

    expect($content)->toContain('not found');
});

it('counts menu items correctly', function () {
    $menu = Menu::factory()->create([
        'items' => [
            ['title' => 'Item 1', 'url' => '/1'],
            ['title' => 'Item 2', 'url' => '/2'],
            [
                'title' => 'Item 3',
                'url' => '/3',
                'children' => [
                    ['title' => 'Sub Item 1', 'url' => '/3/1'],
                ],
            ],
        ],
    ]);

    $tool = app(GetMenus::class);
    $response = $tool->handle(new Request());

    $result = decodeResponse($response);

    $foundMenu = collect($result['menus'])->firstWhere('id', $menu->id);
    expect($foundMenu['items_count'])->toBe(3); // Top-level items only
});

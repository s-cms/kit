<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use SmartCms\Kit\Models\Page;
use SmartCms\Lang\Database\Factories\LanguageFactory;
use SmartCms\Lang\Models\Language;

uses(RefreshDatabase::class);

it('creates home page successfully', function () {
    LanguageFactory::new()->create([
        'slug' => 'en',
        'is_default' => true,
    ]);

    $this->artisan('make:home-page')
        ->expectsOutput('Home page created successfully')
        ->assertExitCode(0);

    $homePage = Page::find(1);

    expect($homePage)->not->toBeNull();
    expect($homePage->id)->toBe(1);
    expect($homePage->slug)->toBe('');
    expect($homePage->is_system)->toBeTrue();
});

it('creates home page with empty slug', function () {
    LanguageFactory::new()->create([
        'slug' => 'en',
        'is_default' => true,
    ]);

    $this->artisan('make:home-page')
        ->assertExitCode(0);

    $homePage = Page::find(1);

    expect($homePage->slug)->toBe('');
});

it('creates home page with system flag', function () {
    LanguageFactory::new()->create([
        'slug' => 'en',
        'is_default' => true,
    ]);

    $this->artisan('make:home-page')
        ->assertExitCode(0);

    $homePage = Page::find(1);

    expect($homePage->is_system)->toBeTrue();
});

it('creates home page with active status', function () {
    LanguageFactory::new()->create([
        'slug' => 'en',
        'is_default' => true,
    ]);

    $this->artisan('make:home-page')
        ->assertExitCode(0);

    $homePage = Page::find(1);

    expect($homePage->status)->toBeTruthy();
});

it('updates existing home page instead of creating duplicate', function () {
    LanguageFactory::new()->create([
        'slug' => 'en',
        'is_default' => true,
    ]);

    // Create home page first time
    $this->artisan('make:home-page')
        ->assertExitCode(0);

    // Run command again
    $this->artisan('make:home-page')
        ->assertExitCode(0);

    // Should still have only one home page with id=1
    expect(Page::find(1))->not->toBeNull();
    expect(Page::where('id', '!=', 1)->count())->toBe(0);
});

it('creates english language if no languages exist', function () {
    // No languages in database
    expect(Language::count())->toBe(0);

    $this->artisan('make:home-page')
        ->assertExitCode(0);

    expect(Language::count())->toBe(1);
    expect(Language::where('slug', 'en')->exists())->toBeTrue();
});

it('uses default language slug for home page name', function () {
    LanguageFactory::new()->create([
        'name' => 'Ukrainian',
        'slug' => 'uk',
        'is_default' => true,
    ]);

    $this->artisan('make:home-page')
        ->assertExitCode(0);

    $homePage = Page::find(1);

    expect($homePage->name)->toBeArray();
    expect($homePage->name)->toHaveKey('uk');
    expect($homePage->name['uk'])->toBe('Home');
});

it('sets correct default language attributes when creating language', function () {
    expect(Language::count())->toBe(0);

    $this->artisan('make:home-page')
        ->assertExitCode(0);

    $language = Language::where('slug', 'en')->first();

    expect($language->is_default)->toBeTrue();
    expect($language->is_admin_active)->toBeTrue();
    expect($language->is_frontend_active)->toBeTrue();
});

it('home page has correct structure', function () {
    LanguageFactory::new()->create(['slug' => 'en', 'is_default' => true]);

    $this->artisan('make:home-page')
        ->assertExitCode(0);

    $homePage = Page::find(1);

    expect($homePage)->toHaveKeys([
        'id',
        'name',
        'slug',
        'status',
        'is_system',
    ]);
});

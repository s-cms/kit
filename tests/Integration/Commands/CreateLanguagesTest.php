<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use SmartCms\Lang\Models\Language;

uses(RefreshDatabase::class);

it('creates all default languages', function () {
    $this->artisan('kit:create-languages')
        ->assertExitCode(0);

    expect(Language::count())->toBe(12);
});

it('creates english as default language', function () {
    $this->artisan('kit:create-languages')
        ->assertExitCode(0);

    $english = Language::where('slug', 'en')->first();

    expect($english)->not->toBeNull();
    expect($english->name)->toBe('English');
    expect($english->is_default)->toBeTrue();
    expect($english->is_admin_active)->toBeTrue();
    expect($english->is_frontend_active)->toBeTrue();
});

it('creates all expected language slugs', function () {
    $expectedSlugs = ['en', 'ru', 'uk', 'pl', 'de', 'fr', 'es', 'it', 'pt', 'zh', 'ja', 'ko'];

    $this->artisan('kit:create-languages')
        ->assertExitCode(0);

    foreach ($expectedSlugs as $slug) {
        expect(Language::where('slug', $slug)->exists())->toBeTrue();
    }
});

it('sets non-english languages as inactive by default', function () {
    $this->artisan('kit:create-languages')
        ->assertExitCode(0);

    $russian = Language::where('slug', 'ru')->first();

    expect($russian->is_default)->toBeFalse();
    expect($russian->is_admin_active)->toBeFalse();
    expect($russian->is_frontend_active)->toBeFalse();
});

it('sets correct locale for each language', function () {
    $expectedLocales = [
        'en' => 'en_US',
        'ru' => 'ru_RU',
        'uk' => 'uk_UA',
        'de' => 'de_DE',
        'fr' => 'fr_FR',
    ];

    $this->artisan('kit:create-languages')
        ->assertExitCode(0);

    foreach ($expectedLocales as $slug => $locale) {
        $language = Language::where('slug', $slug)->first();
        expect($language->locale)->toBe($locale);
    }
});

it('deletes existing languages before creating new ones', function () {
    // Create some existing languages
    Language::factory()->create(['slug' => 'en']);
    Language::factory()->create(['slug' => 'de']);

    expect(Language::count())->toBe(2);

    $this->artisan('kit:create-languages')
        ->assertExitCode(0);

    // Should have exactly 12 languages (the default set)
    expect(Language::count())->toBe(12);
});

it('creates ukrainian language with correct name', function () {
    $this->artisan('kit:create-languages')
        ->assertExitCode(0);

    $ukrainian = Language::where('slug', 'uk')->first();

    expect($ukrainian->name)->toBe('Українська');
    expect($ukrainian->locale)->toBe('uk_UA');
});

it('creates russian language with cyrillic name', function () {
    $this->artisan('kit:create-languages')
        ->assertExitCode(0);

    $russian = Language::where('slug', 'ru')->first();

    expect($russian->name)->toBe('Русский');
});

it('creates asian languages with correct names', function () {
    $this->artisan('kit:create-languages')
        ->assertExitCode(0);

    $chinese = Language::where('slug', 'zh')->first();
    $japanese = Language::where('slug', 'ja')->first();
    $korean = Language::where('slug', 'ko')->first();

    expect($chinese->name)->toBe('中文');
    expect($japanese->name)->toBe('日本語');
    expect($korean->name)->toBe('한국어');
});

it('creates european languages correctly', function () {
    $expectedLanguages = [
        'de' => 'Deutsch',
        'fr' => 'Français',
        'es' => 'Español',
        'it' => 'Italiano',
        'pt' => 'Português',
        'pl' => 'Polski',
    ];

    $this->artisan('kit:create-languages')
        ->assertExitCode(0);

    foreach ($expectedLanguages as $slug => $name) {
        $language = Language::where('slug', $slug)->first();
        expect($language->name)->toBe($name);
    }
});

it('only english is active by default', function () {
    $this->artisan('kit:create-languages')
        ->assertExitCode(0);

    $activeLanguages = Language::where('is_admin_active', true)->get();
    $defaultLanguages = Language::where('is_default', true)->get();

    expect($activeLanguages)->toHaveCount(1);
    expect($defaultLanguages)->toHaveCount(1);
    expect($activeLanguages->first()->slug)->toBe('en');
    expect($defaultLanguages->first()->slug)->toBe('en');
});

it('can be run multiple times safely', function () {
    $this->artisan('kit:create-languages')->assertExitCode(0);
    $this->artisan('kit:create-languages')->assertExitCode(0);
    $this->artisan('kit:create-languages')->assertExitCode(0);

    expect(Language::count())->toBe(12);
});

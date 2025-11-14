<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use SmartCms\Kit\Models\Block;
use SmartCms\Kit\Models\Blockable;
use SmartCms\Kit\Models\Page;
use SmartCms\Lang\Database\Factories\LanguageFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    LanguageFactory::new()->create(['slug' => 'en']);
});

it('can create a block', function () {
    $block = Block::factory()->create([
        'type' => 'TestBlock',
        'title' => 'Test Block',
    ]);

    expect($block)->toBeInstanceOf(Block::class);
    expect($block->type)->toBe('TestBlock');
    expect($block->title)->toBe('Test Block');
});

it('stores schema as array', function () {
    $schema = [
        'type' => 'object',
        'properties' => [
            'title' => ['type' => 'string', 'default' => ''],
        ],
    ];

    $block = Block::factory()->create(['schema' => $schema]);

    expect($block->schema)->toBeArray();
    expect($block->schema)->toHaveKey('type');
    expect($block->schema['type'])->toBe('object');
});

it('stores data as array', function () {
    $data = [
        'en' => [
            'title' => 'Test Title',
            'subtitle' => 'Test Subtitle',
        ],
    ];

    $block = Block::factory()->create(['data' => $data]);

    expect($block->data)->toBeArray();
    expect($block->data)->toHaveKey('en');
});

it('handles multilingual data', function () {
    $block = Block::factory()->withLanguages(['en', 'uk'])->create();

    $enData = $block->getTranslation('data', 'en');
    $ukData = $block->getTranslation('data', 'uk');

    expect($enData)->toBeArray();
    expect($ukData)->toBeArray();
    expect($enData)->toHaveKey('title');
    expect($ukData)->toHaveKey('title');
});

it('returns transformed data for current locale', function () {
    $block = Block::factory()->create([
        'schema' => [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string', 'default' => ''],
            ],
        ],
        'data' => [
            'en' => ['title' => 'Test Title'],
        ],
    ]);

    $transformed = $block->transformedData();

    expect($transformed)->toBeArray();
    expect($transformed)->toHaveKey('title');
    expect($transformed['title'])->toBe('Test Title');
});

it('returns transformed data for specific locale', function () {
    $block = Block::factory()->withLanguages(['en', 'uk'])->create();

    $enData = $block->getTransformedData('en');
    $ukData = $block->getTransformedData('uk');

    expect($enData)->toBeArray();
    expect($ukData)->toBeArray();
});

it('returns empty array when no schema is defined', function () {
    $block = Block::factory()->create(['schema' => null]);

    $transformed = $block->getTransformedData();

    expect($transformed)->toBeArray();
});

it('returns empty array when schema has no properties', function () {
    $block = Block::factory()->create([
        'schema' => ['type' => 'object'],
        'data' => ['en' => ['title' => 'Test']],
    ]);

    $transformed = $block->getTransformedData();

    expect($transformed)->toBeArray();
});

it('returns transformed data for all locales', function () {
    $block = Block::factory()->withLanguages(['en', 'uk', 'de'])->create();

    $allData = $block->getTransformedDataForAllLocales();

    expect($allData)->toBeArray();
    expect($allData)->toHaveKeys(['en', 'uk', 'de']);
});

it('can be active', function () {
    $block = Block::factory()->active()->create();

    expect($block->status)->toBeTrue();
});

it('can be inactive', function () {
    $block = Block::factory()->inactive()->create();

    expect($block->status)->toBeFalse();
});

it('has blockables relationship', function () {
    $block = Block::factory()->create();

    expect($block->blockables())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

it('has pages relationship', function () {
    $block = Block::factory()->create();

    expect($block->pages())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphToMany::class);
});

it('can be attached to pages', function () {
    $block = Block::factory()->create();
    $page = Page::factory()->create();

    $page->blocks()->attach($block->id, [
        'status' => true,
        'sorting' => 1,
    ]);

    expect($block->pages)->toHaveCount(1);
    expect($block->pages->first()->id)->toBe($page->id);
});

it('orders pages by sorting', function () {
    $block = Block::factory()->create();
    $page1 = Page::factory()->create();
    $page2 = Page::factory()->create();

    $page1->blocks()->attach($block->id, ['status' => true, 'sorting' => 2]);
    $page2->blocks()->attach($block->id, ['status' => true, 'sorting' => 1]);

    $pages = $block->pages()->get();

    expect($pages->first()->id)->toBe($page2->id);
    expect($pages->last()->id)->toBe($page1->id);
});

it('can filter active blockables', function () {
    $block = Block::factory()->create();
    $page1 = Page::factory()->create();
    $page2 = Page::factory()->create();

    // Active blockable
    $page1->blocks()->attach($block->id, ['status' => true, 'sorting' => 1]);
    // Inactive blockable
    $page2->blocks()->attach($block->id, ['status' => false, 'sorting' => 2]);

    $activeBlockables = $block->activeBlockables;

    expect($activeBlockables)->toHaveCount(1);
});

it('creates hero section with correct schema', function () {
    $block = Block::factory()->heroSection()->create();

    expect($block->type)->toBe('HeroSection');
    expect($block->schema)->toHaveKey('properties');
    expect($block->schema['properties'])->toHaveKeys(['title', 'subtitle', 'cta_text', 'cta_link', 'background_image']);
});

it('creates header section with menu field', function () {
    $block = Block::factory()->headerSection()->create();

    expect($block->type)->toBe('HeaderSection');
    expect($block->schema['properties'])->toHaveKey('main_navigation');
    expect($block->schema['properties']['main_navigation']['inputType'])->toBe('menu');
});

it('creates footer section with copyright', function () {
    $block = Block::factory()->footerSection()->create();

    expect($block->type)->toBe('FooterSection');
    expect($block->data['en'])->toHaveKey('copyright');
});

it('can create empty block', function () {
    $block = Block::factory()->empty()->create();

    expect($block->data['en'])->toBeEmpty();
});

it('can create block with nested data', function () {
    $block = Block::factory()->withNestedData()->create();

    expect($block->schema['properties'])->toHaveKey('sections');
    expect($block->data['en']['sections'])->toBeArray();
    expect($block->data['en']['sections'])->toHaveCount(2);
});

it('can create block with custom schema', function () {
    $customSchema = [
        'type' => 'object',
        'properties' => [
            'custom_field' => ['type' => 'string', 'default' => 'custom value'],
        ],
    ];

    $block = Block::factory()->withSchema($customSchema)->create();

    expect($block->schema)->toBe($customSchema);
    expect($block->schema['properties'])->toHaveKey('custom_field');
});

it('can create block with custom data', function () {
    $customData = [
        'en' => ['custom_field' => 'custom value'],
    ];

    $block = Block::factory()->withData($customData)->create();

    expect($block->data)->toBe($customData);
});

it('uses custom table name from config', function () {
    config(['kit.blocks_table_name' => 'custom_blocks']);

    $block = new Block;

    expect($block->getTable())->toBe('custom_blocks');
});

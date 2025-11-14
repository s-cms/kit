<?php

use SmartCms\Kit\Actions\Microdata\BreadcrumbsMicrodata;
use SmartCms\Kit\Actions\Microdata\OrganizationMicrodata;
use SmartCms\Kit\Actions\Microdata\WebsiteMicrodata;

it('generates website microdata', function () {
    $action = new WebsiteMicrodata;
    $microdata = $action->handle();

    expect($microdata)->toBeArray();
    expect($microdata)->toHaveKeys(['@context', '@type', 'url']);
    expect($microdata['@context'])->toBe('https://schema.org');
    expect($microdata['@type'])->toBe('WebSite');
    expect($microdata['url'])->toContain(url('/'));
});

it('website microdata has correct schema.org context', function () {
    $action = new WebsiteMicrodata;
    $microdata = $action->handle();

    expect($microdata['@context'])->toBe('https://schema.org');
});

it('website microdata has WebSite type', function () {
    $action = new WebsiteMicrodata;
    $microdata = $action->handle();

    expect($microdata['@type'])->toBe('WebSite');
});

it('generates organization microdata', function () {
    $action = new OrganizationMicrodata;
    $microdata = $action->handle();

    expect($microdata)->toBeArray();
    expect($microdata)->toHaveKeys(['@context', '@type', 'name', 'url', 'logo']);
    expect($microdata['@context'])->toBe('https://schema.org');
    expect($microdata['@type'])->toBe('Organization');
});

it('organization microdata has Organization type', function () {
    $action = new OrganizationMicrodata;
    $microdata = $action->handle();

    expect($microdata['@type'])->toBe('Organization');
});

it('organization microdata includes company name', function () {
    $action = new OrganizationMicrodata;
    $microdata = $action->handle();

    expect($microdata)->toHaveKey('name');
});

it('organization microdata includes logo', function () {
    $action = new OrganizationMicrodata;
    $microdata = $action->handle();

    expect($microdata)->toHaveKey('logo');
});

it('generates breadcrumbs microdata with empty array', function () {
    $action = new BreadcrumbsMicrodata;
    $microdata = $action->handle([]);

    expect($microdata)->toBeArray();
    expect($microdata)->toHaveKeys(['@context', '@type', 'itemListElement']);
    expect($microdata['@context'])->toBe('https://schema.org');
    expect($microdata['@type'])->toBe('BreadcrumbList');
});

it('breadcrumbs microdata has correct type', function () {
    $action = new BreadcrumbsMicrodata;
    $microdata = $action->handle([]);

    expect($microdata['@type'])->toBe('BreadcrumbList');
});

it('breadcrumbs microdata includes home item by default', function () {
    $action = new BreadcrumbsMicrodata;
    $microdata = $action->handle([]);

    expect($microdata['itemListElement'])->toHaveCount(1);
    expect($microdata['itemListElement'][0]['position'])->toBe(1);
    expect($microdata['itemListElement'][0]['@type'])->toBe('ListItem');
});

it('breadcrumbs microdata processes single breadcrumb', function () {
    $action = new BreadcrumbsMicrodata;

    $breadcrumbs = [
        ['name' => 'About Us', 'link' => url('/about')],
    ];

    $microdata = $action->handle($breadcrumbs);

    expect($microdata['itemListElement'])->toHaveCount(2); // Home + About Us
    expect($microdata['itemListElement'][1]['name'])->toBe('About Us');
    expect($microdata['itemListElement'][1]['position'])->toBe(1);
});

it('breadcrumbs microdata processes multiple breadcrumbs', function () {
    $action = new BreadcrumbsMicrodata;

    $breadcrumbs = [
        ['name' => 'Products', 'link' => url('/products')],
        ['name' => 'Category', 'link' => url('/products/category')],
        ['name' => 'Item', 'link' => url('/products/category/item')],
    ];

    $microdata = $action->handle($breadcrumbs);

    expect($microdata['itemListElement'])->toHaveCount(4); // Home + 3 breadcrumbs
    expect($microdata['itemListElement'][1]['name'])->toBe('Products');
    expect($microdata['itemListElement'][2]['name'])->toBe('Category');
    expect($microdata['itemListElement'][3]['name'])->toBe('Item');
});

it('breadcrumbs microdata sets correct positions', function () {
    $action = new BreadcrumbsMicrodata;

    $breadcrumbs = [
        ['name' => 'First', 'link' => url('/first')],
        ['name' => 'Second', 'link' => url('/second')],
    ];

    $microdata = $action->handle($breadcrumbs);

    expect($microdata['itemListElement'][0]['position'])->toBe(1); // Home
    expect($microdata['itemListElement'][1]['position'])->toBe(1); // First
    expect($microdata['itemListElement'][2]['position'])->toBe(2); // Second
});

it('breadcrumbs microdata includes links', function () {
    $action = new BreadcrumbsMicrodata;

    $breadcrumbs = [
        ['name' => 'Page', 'link' => url('/page')],
    ];

    $microdata = $action->handle($breadcrumbs);

    expect($microdata['itemListElement'][1]['item'])->toBe(url('/page'));
});

it('breadcrumbs microdata handles missing name with hostname', function () {
    $action = new BreadcrumbsMicrodata;

    $breadcrumbs = [
        ['link' => url('/page')],
    ];

    $microdata = $action->handle($breadcrumbs);

    expect($microdata['itemListElement'][1])->toHaveKey('name');
});

it('breadcrumbs microdata handles missing link with default url', function () {
    $action = new BreadcrumbsMicrodata;

    $breadcrumbs = [
        ['name' => 'Page'],
    ];

    $microdata = $action->handle($breadcrumbs);

    expect($microdata['itemListElement'][1]['item'])->toBe(url('/'));
});

it('all microdata actions use schema.org context', function (string $className) {
    $action = new $className;
    $microdata = $action->handle([]);

    expect($microdata['@context'])->toBe('https://schema.org');
})->with([
    [WebsiteMicrodata::class],
    [OrganizationMicrodata::class],
    [BreadcrumbsMicrodata::class],
]);

it('all microdata actions return arrays', function (string $className) {
    $action = new $className;
    $microdata = $action->handle([]);

    expect($microdata)->toBeArray();
})->with([
    [WebsiteMicrodata::class],
    [OrganizationMicrodata::class],
    [BreadcrumbsMicrodata::class],
]);

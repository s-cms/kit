<?php

use SmartCms\Kit\VariableTypes\AddressType;
use SmartCms\Kit\VariableTypes\EmailsType;
use SmartCms\Kit\VariableTypes\EmailType;
use SmartCms\Kit\VariableTypes\FormType;
use SmartCms\Kit\VariableTypes\ImageType;
use SmartCms\Kit\VariableTypes\KeyValueType;
use SmartCms\Kit\VariableTypes\LinkType;
use SmartCms\Kit\VariableTypes\MenuType;
use SmartCms\Kit\VariableTypes\PhoneType;
use SmartCms\Kit\VariableTypes\SocialsType;
use SmartCms\Kit\VariableTypes\StringType;

it('all variable types implement required interface methods', function (string $className) {
    $type = $className::make();

    expect($type)->toHaveMethod('getName');
    expect($type)->toHaveMethod('getDefaultValue');
    expect($type)->toHaveMethod('getSchema');
    expect($type)->toHaveMethod('getValue');
})->with([
    [StringType::class],
    [ImageType::class],
    [MenuType::class],
    [EmailType::class],
    [EmailsType::class],
    [FormType::class],
    [LinkType::class],
    [PhoneType::class],
    [SocialsType::class],
    [AddressType::class],
    [KeyValueType::class],
]);

it('all variable types have unique names', function (string $className, string $expectedName) {
    expect($className::getName())->toBe($expectedName);
})->with([
    [StringType::class, 'string'],
    [ImageType::class, 'image'],
    [MenuType::class, 'menu'],
    [EmailType::class, 'email'],
    [FormType::class, 'form'],
    [LinkType::class, 'link'],
    [PhoneType::class, 'phone'],
]);

it('all variable types can be instantiated with make', function (string $className) {
    $instance = $className::make();

    expect($instance)->toBeInstanceOf($className);
})->with([
    [StringType::class],
    [ImageType::class],
    [MenuType::class],
    [EmailType::class],
]);

it('string type returns default text', function () {
    $type = StringType::make();

    expect($type->getDefaultValue())->toBe('Default text');
});

it('string type returns value or default', function () {
    $type = StringType::make();

    expect($type->getValue('Test'))->toBe('Test');
    expect($type->getValue(null))->toBe('Default text');
});

it('string type generates text input schema', function () {
    $type = StringType::make();
    $schema = $type->getSchema('test_field');

    expect($schema)->toBeInstanceOf(\Filament\Forms\Components\TextInput::class);
});

it('image type returns default image structure', function () {
    $type = ImageType::make();
    $default = $type->getDefaultValue();

    expect($default)->toBeArray();
    expect($default)->toHaveKeys(['width', 'height', 'source', 'alt']);
    expect($default['width'])->toBe(300);
    expect($default['height'])->toBe(300);
});

it('image type returns default when value is not array', function () {
    $type = ImageType::make();

    $result = $type->getValue('invalid');

    expect($result)->toBeArray();
    expect($result)->toHaveKey('source');
});

it('menu type returns empty array as default', function () {
    $type = MenuType::make();

    expect($type->getDefaultValue())->toBe([]);
});

it('menu type generates select schema', function () {
    $type = MenuType::make();
    $schema = $type->getSchema('test_menu');

    expect($schema)->toBeInstanceOf(\Filament\Forms\Components\Select::class);
});

it('email type returns default email', function () {
    $type = EmailType::make();

    expect($type->getDefaultValue())->toBe('example@example.com');
});

it('email type returns default when value not found', function () {
    app('s')->shouldReceive('get')
        ->with('company_info.emails', [])
        ->andReturn([]);

    $type = EmailType::make();
    $result = $type->getValue('nonexistent');

    expect($result)->toBe('example@example.com');
});

it('email type retrieves email from settings', function () {
    app('s')->shouldReceive('get')
        ->with('company_info.emails', [])
        ->andReturn([
            'primary' => ['value' => 'primary@example.com'],
            'support' => ['value' => 'support@example.com'],
        ]);

    $type = EmailType::make();
    $result = $type->getValue('primary');

    expect($result)->toBe('primary@example.com');
});

it('link type generates correct schema', function () {
    $type = LinkType::make();
    $schema = $type->getSchema('test_link');

    expect($schema)->toBeInstanceOf(\Filament\Schemas\Components\Component::class);
});

it('link type returns default link structure', function () {
    $type = LinkType::make();
    $default = $type->getDefaultValue();

    expect($default)->toBeArray();
    expect($default)->toHaveKeys(['title', 'is_external', 'url']);
    expect($default['is_external'])->toBeFalse();
});

it('link type parses internal link correctly', function () {
    $type = LinkType::make();

    $value = [
        'title' => 'Test Link',
        'url' => '/about',
        'is_external' => false,
    ];

    $result = $type->getValue($value);

    expect($result['title'])->toBe('Test Link');
    expect($result['is_external'])->toBeFalse();
});

it('link type handles external links', function () {
    $type = LinkType::make();

    $value = [
        'title' => 'External Link',
        'url' => 'https://example.com',
        'is_external' => true,
    ];

    $result = $type->getValue($value);

    expect($result['is_external'])->toBeTrue();
    expect($result['url'])->toBe('https://example.com');
});

it('phone type returns default phone', function () {
    $type = PhoneType::make();

    expect($type->getDefaultValue())->toBe('+1234567890');
});

it('address type returns default address', function () {
    $type = AddressType::make();

    expect($type->getDefaultValue())->toBe('123 Main St, City, Country');
});

it('key value type returns empty array as default', function () {
    $type = KeyValueType::make();

    expect($type->getDefaultValue())->toBe([]);
});

it('form type returns empty array as default', function () {
    $type = FormType::make();

    expect($type->getDefaultValue())->toBe([]);
});

it('socials type returns default social links', function () {
    $type = SocialsType::make();
    $default = $type->getDefaultValue();

    expect($default)->toBeArray();
});

it('socials type retrieves from settings', function () {
    app('s')->shouldReceive('get')
        ->with('company_info.socials', [])
        ->andReturn([
            ['platform' => 'facebook', 'url' => 'https://facebook.com/page'],
            ['platform' => 'twitter', 'url' => 'https://twitter.com/user'],
        ]);

    $type = SocialsType::make();
    $result = $type->getValue(null);

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
});

it('emails type returns empty array as default', function () {
    $type = EmailsType::make();

    expect($type->getDefaultValue())->toBe([]);
});

it('emails type retrieves multiple emails from settings', function () {
    app('s')->shouldReceive('get')
        ->with('company_info.emails', [])
        ->andReturn([
            'primary' => ['value' => 'primary@example.com'],
            'support' => ['value' => 'support@example.com'],
        ]);

    $type = EmailsType::make();
    $result = $type->getValue(null);

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
});

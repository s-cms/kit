<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use SmartCms\Kit\Models\Admin;
use Filament\Panel;

uses(RefreshDatabase::class);

it('can create an admin', function () {
    $admin = Admin::factory()->create([
        'username' => 'testadmin',
        'email' => 'test@example.com',
    ]);

    expect($admin)->toBeInstanceOf(Admin::class);
    expect($admin->username)->toBe('testadmin');
    expect($admin->email)->toBe('test@example.com');
});

it('hashes password automatically', function () {
    $admin = Admin::factory()->create([
        'password' => 'plain-password',
    ]);

    expect($admin->password)->not->toBe('plain-password');
    expect(Hash::check('plain-password', $admin->password))->toBeTrue();
});

it('hides password in array representation', function () {
    $admin = Admin::factory()->create();

    $array = $admin->toArray();

    expect($array)->not->toHaveKey('password');
});

it('hides remember_token in array representation', function () {
    $admin = Admin::factory()->create();

    $array = $admin->toArray();

    expect($array)->not->toHaveKey('remember_token');
});

it('can access filament panel', function () {
    $admin = Admin::factory()->create();
    $panel = Mockery::mock(Panel::class);

    expect($admin->canAccessPanel($panel))->toBeTrue();
});

it('returns username as name attribute', function () {
    $admin = Admin::factory()->create(['username' => 'johndoe']);

    expect($admin->name)->toBe('johndoe');
});

it('returns default name when username is null', function () {
    $admin = new Admin(['username' => null]);

    expect($admin->name)->toBe('Admin');
});

it('stores notifications as array', function () {
    $notifications = ['email' => true, 'telegram' => false];

    $admin = Admin::factory()->create(['notifications' => $notifications]);

    expect($admin->notifications)->toBeArray();
    expect($admin->notifications)->toBe($notifications);
});

it('can store telegram id', function () {
    $admin = Admin::factory()->create(['telegram_id' => '123456789']);

    expect($admin->telegram_id)->toBe('123456789');
});

it('has unique email', function () {
    $email = 'unique@example.com';

    Admin::factory()->create(['email' => $email]);

    expect(fn () => Admin::factory()->create(['email' => $email]))
        ->toThrow(\Exception::class);
});

it('has unique username', function () {
    $username = 'uniqueuser';

    Admin::factory()->create(['username' => $username]);

    expect(fn () => Admin::factory()->create(['username' => $username]))
        ->toThrow(\Exception::class);
});

it('can update password', function () {
    $admin = Admin::factory()->create(['password' => 'old-password']);

    $admin->update(['password' => 'new-password']);

    $admin->refresh();

    expect(Hash::check('new-password', $admin->password))->toBeTrue();
    expect(Hash::check('old-password', $admin->password))->toBeFalse();
});

it('uses custom table name from config', function () {
    config(['kit.admins_table_name' => 'custom_admins']);

    $admin = new Admin;

    expect($admin->getTable())->toBe('custom_admins');
});

it('can send notifications', function () {
    $admin = Admin::factory()->create();

    // Just check that the Notifiable trait is working
    expect($admin)->toHaveMethod('notify');
});

it('generates remember token', function () {
    $admin = Admin::factory()->create();

    expect($admin->remember_token)->not->toBeNull();
    expect($admin->remember_token)->toBeString();
});

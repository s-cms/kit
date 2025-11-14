<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use SmartCms\Kit\Models\Admin;

uses(RefreshDatabase::class);

it('creates an admin successfully', function () {
    $this->artisan('make:admin')
        ->expectsQuestion('Enter admin username', 'testadmin')
        ->expectsQuestion('Enter admin email', 'test@example.com')
        ->expectsQuestion('Enter admin password', 'password123')
        ->expectsOutput('Admin created successfully')
        ->assertExitCode(0);

    $admin = Admin::where('username', 'testadmin')->first();

    expect($admin)->not->toBeNull();
    expect($admin->email)->toBe('test@example.com');
    expect(Hash::check('password123', $admin->password))->toBeTrue();
});

it('prevents duplicate username', function () {
    Admin::factory()->create(['username' => 'existing']);

    $this->artisan('make:admin')
        ->expectsQuestion('Enter admin username', 'existing')
        ->expectsOutput('Admin already exists')
        ->assertExitCode(0);

    expect(Admin::where('username', 'existing')->count())->toBe(1);
});

it('prevents duplicate email', function () {
    Admin::factory()->create(['email' => 'existing@example.com']);

    $this->artisan('make:admin')
        ->expectsQuestion('Enter admin username', 'newadmin')
        ->expectsQuestion('Enter admin email', 'existing@example.com')
        ->expectsOutput('Admin already exists')
        ->assertExitCode(0);

    expect(Admin::where('email', 'existing@example.com')->count())->toBe(1);
});

it('hashes password before storing', function () {
    $this->artisan('make:admin')
        ->expectsQuestion('Enter admin username', 'secureadmin')
        ->expectsQuestion('Enter admin email', 'secure@example.com')
        ->expectsQuestion('Enter admin password', 'myplainpassword')
        ->assertExitCode(0);

    $admin = Admin::where('username', 'secureadmin')->first();

    expect($admin->password)->not->toBe('myplainpassword');
    expect(Hash::check('myplainpassword', $admin->password))->toBeTrue();
});

it('creates multiple admins with different usernames', function () {
    $this->artisan('make:admin')
        ->expectsQuestion('Enter admin username', 'admin1')
        ->expectsQuestion('Enter admin email', 'admin1@example.com')
        ->expectsQuestion('Enter admin password', 'password1')
        ->assertExitCode(0);

    $this->artisan('make:admin')
        ->expectsQuestion('Enter admin username', 'admin2')
        ->expectsQuestion('Enter admin email', 'admin2@example.com')
        ->expectsQuestion('Enter admin password', 'password2')
        ->assertExitCode(0);

    expect(Admin::count())->toBe(2);
    expect(Admin::where('username', 'admin1')->exists())->toBeTrue();
    expect(Admin::where('username', 'admin2')->exists())->toBeTrue();
});

it('handles special characters in username', function () {
    $this->artisan('make:admin')
        ->expectsQuestion('Enter admin username', 'admin_123')
        ->expectsQuestion('Enter admin email', 'admin123@example.com')
        ->expectsQuestion('Enter admin password', 'password')
        ->assertExitCode(0);

    expect(Admin::where('username', 'admin_123')->exists())->toBeTrue();
});

it('handles complex passwords', function () {
    $complexPassword = 'C0mpl3x!P@ssw0rd#123';

    $this->artisan('make:admin')
        ->expectsQuestion('Enter admin username', 'secureadmin')
        ->expectsQuestion('Enter admin email', 'secure@example.com')
        ->expectsQuestion('Enter admin password', $complexPassword)
        ->assertExitCode(0);

    $admin = Admin::where('username', 'secureadmin')->first();

    expect(Hash::check($complexPassword, $admin->password))->toBeTrue();
});

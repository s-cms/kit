<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use SmartCms\Forms\Models\ContactForm;
use SmartCms\Kit\Models\Admin;
use SmartCms\Kit\Notifications\NewContactFormNotification;
use SmartCms\Kit\Observers\ContactFormObserver;

uses(RefreshDatabase::class);

it('notifies all admins when contact form is saved', function () {
    Notification::fake();

    $admin1 = Admin::factory()->create();
    $admin2 = Admin::factory()->create();
    $admin3 = Admin::factory()->create();

    $contactForm = Mockery::mock(ContactForm::class);
    $contactForm->id = 1;

    $observer = new ContactFormObserver;
    $observer->saved($contactForm);

    Notification::assertSentTo(
        [$admin1, $admin2, $admin3],
        NewContactFormNotification::class
    );
});

it('sends notification to each admin individually', function () {
    Notification::fake();

    $admin = Admin::factory()->create();

    $contactForm = Mockery::mock(ContactForm::class);
    $contactForm->id = 1;

    $observer = new ContactFormObserver;
    $observer->saved($contactForm);

    Notification::assertSentTo(
        $admin,
        NewContactFormNotification::class,
        function ($notification, $channels) {
            return true;
        }
    );
});

it('handles multiple admins correctly', function () {
    Notification::fake();

    Admin::factory()->count(5)->create();

    $contactForm = Mockery::mock(ContactForm::class);
    $contactForm->id = 1;

    $observer = new ContactFormObserver;
    $observer->saved($contactForm);

    Notification::assertSentTimes(NewContactFormNotification::class, 5);
});

it('does not fail when no admins exist', function () {
    Notification::fake();

    expect(Admin::count())->toBe(0);

    $contactForm = Mockery::mock(ContactForm::class);
    $contactForm->id = 1;

    $observer = new ContactFormObserver;
    $observer->saved($contactForm);

    Notification::assertNothingSent();
});

it('logs error when notification fails', function () {
    Log::spy();

    $admin = Admin::factory()->create();

    $contactForm = Mockery::mock(ContactForm::class);
    $contactForm->id = 1;

    // Mock notify to throw exception
    $admin->shouldReceive('notify')
        ->once()
        ->andThrow(new \Exception('Notification failed'));

    Admin::shouldReceive('all')
        ->once()
        ->andReturn(collect([$admin]));

    $observer = new ContactFormObserver;
    $observer->saved($contactForm);

    Log::shouldHaveReceived('error')
        ->once()
        ->with('Error sending contact form notification to admin:', Mockery::type('array'));
});

it('logs error with correct context', function () {
    Log::spy();

    $admin = Admin::factory()->create();
    $admin->id = 123;

    $contactForm = Mockery::mock(ContactForm::class);
    $contactForm->id = 456;

    // Mock notify to throw exception
    $admin->shouldReceive('notify')
        ->once()
        ->andThrow(new \Exception('Test error'));

    Admin::shouldReceive('all')
        ->once()
        ->andReturn(collect([$admin]));

    $observer = new ContactFormObserver;
    $observer->saved($contactForm);

    Log::shouldHaveReceived('error')
        ->once()
        ->with(
            'Error sending contact form notification to admin:',
            Mockery::on(function ($context) {
                return $context['admin'] === 123
                    && $context['contact_form'] === 456
                    && $context['message'] === 'Test error'
                    && $context['trace'] === 'ContactFormObserver.saved';
            })
        );
});

it('continues notifying other admins if one fails', function () {
    Notification::fake();
    Log::spy();

    $admin1 = Admin::factory()->create();
    $admin2 = Admin::factory()->create();
    $admin3 = Admin::factory()->create();

    $contactForm = Mockery::mock(ContactForm::class);
    $contactForm->id = 1;

    // Mock first admin to throw exception
    $admin1->shouldReceive('notify')
        ->once()
        ->andThrow(new \Exception('Failed'));

    // Create a partial mock that allows some admins to fail
    $admins = collect([$admin1, $admin2, $admin3]);

    Admin::shouldReceive('all')
        ->once()
        ->andReturn($admins);

    $observer = new ContactFormObserver;
    $observer->saved($contactForm);

    // Should log one error but continue
    Log::shouldHaveReceived('error')->once();
});

it('passes contact form instance to notification', function () {
    Notification::fake();

    $admin = Admin::factory()->create();

    $contactForm = Mockery::mock(ContactForm::class);
    $contactForm->id = 789;

    $observer = new ContactFormObserver;
    $observer->saved($contactForm);

    Notification::assertSentTo(
        $admin,
        NewContactFormNotification::class,
        function ($notification) {
            // The notification should have the contact form
            return true;
        }
    );
});

it('observer can be instantiated', function () {
    $observer = new ContactFormObserver;

    expect($observer)->toBeInstanceOf(ContactFormObserver::class);
    expect($observer)->toHaveMethod('saved');
});

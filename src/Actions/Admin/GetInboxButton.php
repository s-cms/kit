<?php

namespace SmartCms\Kit\Actions\Admin;

use Closure;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use SmartCms\Forms\Enums\ContactFormStatusesEnum;
use SmartCms\Forms\Models\ContactForm;
use SmartCms\Kit\Livewire\InboxButton;

class GetInboxButton
{
    public static function run(): Closure
    {
        Livewire::component('scms-inbox-button', InboxButton::class);

        return function (): string {
            // $count = ContactForm::query()->where('status', ContactFormStatusesEnum::NEW)->count();

            return Blade::render('@livewire(\'scms-inbox-button\')');
        };
    }
}

<?php

namespace SmartCms\Kit\Actions\Admin;

use Closure;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Actions\Action;
use Filament\Support\Enums\IconSize;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire as LivewireLivewire;
use SmartCms\Forms\Admin\Resources\ContactForms\ContactFormResource;
use SmartCms\Forms\Enums\ContactFormStatusesEnum;
use SmartCms\Forms\Models\ContactForm;
use SmartCms\Kit\Livewire\DatabaseNotification;

class GetViewButton
{
    public static function run(): Closure
    {
        $count = ContactForm::query()->where('status', ContactFormStatusesEnum::NEW)->count();
        LivewireLivewire::component('scms-database-notifications', DatabaseNotification::class);
        return fn(): string => Blade::render('<div style="display:flex; align-items:center; justify-content:flex-end; gap:10px; padding-inline:.5rem;">{{$action}} {{$action2}} @livewire(\'scms-database-notifications\')</div>', [
            'action' => Action::make('view')
                ->hiddenLabel()
                ->icon(LucideIcon::ExternalLink)
                ->button()
                ->extraAttributes([
                    'style' => 'width:30%'
                ])
                ->iconSize(IconSize::Large)
                ->color('gray')
                ->url(url('/'))
                ->openUrlInNewTab(),
            'action2' => Action::make('contact_form')
                ->hiddenLabel()
                ->button()
                ->badge($count)
                ->extraAttributes([
                    'style' => 'width:30%'
                ])
                ->badgeColor($count > 0 ? 'warning' : 'gray')
                ->icon(LucideIcon::Inbox)
                ->iconSize(IconSize::Large)
                ->outlined()
                ->color('gray')
                ->url(ContactFormResource::getUrl()),
        ]);
    }
}

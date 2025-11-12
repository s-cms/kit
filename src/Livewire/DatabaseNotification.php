<?php

namespace SmartCms\Kit\Livewire;

use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Actions\Action;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Facades\Filament;
use Filament\Notifications\Livewire\DatabaseNotifications as BaseComponent;
use Filament\Support\Enums\IconSize;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class DatabaseNotification extends BaseComponent
{
    #[Locked]
    public ?DatabaseNotificationsPosition $position = null;

    public function getUser(): Model | Authenticatable | null
    {
        return Filament::auth()->user();
    }

    public function getPollingInterval(): ?string
    {
        return Filament::getDatabaseNotificationsPollingInterval();
    }

    public function getTrigger(): ?View
    {
        return view('kit::notification', [
            'action' => Action::make('contact_form')
                ->label(__('kit::admin.inbox'))
                ->hiddenLabel()
                ->button()
                ->iconSize(IconSize::Large)
                ->icon(LucideIcon::Bell)
                ->outlined()
                ->color('gray')
        ]);
    }
}

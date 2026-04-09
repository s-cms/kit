<?php

namespace SmartCms\Kit\Admin\Pages;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use NotificationChannels\Telegram\TelegramUpdates;
use SmartCms\Kit\Models\Admin;

class Profile extends EditProfile
{
    public function form(Schema $form): Schema
    {
        return $form->schema([
            Tabs::make('tabs')->schema([
                Tab::make(__('kit::admin.general'))->schema([
                    TextInput::make('username')
                        ->label(__('kit::admin.username'))
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    $this->getEmailFormComponent(),
                    Select::make('locale')
                        ->label(__('kit::admin.preferred_language'))
                        ->options([
                            'en' => 'English',
                            'uk' => 'Українська',
                            'pl' => 'Polski',
                            'de' => 'Deutsch',
                        ])
                        ->default('en')
                        ->required(),
                    TextInput::make('telegram_token')->disabled()->hidden()->formatStateUsing(fn ($get) => Str::random(32)),
                    Section::make(__('kit::admin.telegram'))->schema([
                        TextInput::make('telegram_id')
                            ->label(__('kit::admin.telegram_chat_id'))
                            ->suffixActions(
                                []
                            )->readOnly(),
                    ])
                        ->afterHeader([
                            Action::make('copy_telegram_link')
                                ->label(__('kit::admin.copy_telegram_link'))
                                ->icon('heroicon-o-link')
                                ->url(function ($get): string {
                                    $token = $get('telegram_token');
                                    $botUsername = setting('telegram.bot_username');
                                    $botUsername = str_replace('@', '', $botUsername);

                                    return "https://t.me/{$botUsername}?start={$token}";
                                })
                                ->openUrlInNewTab(),
                            Action::make('get_telegram_id')
                                ->label(__('kit::admin.get_telegram_id'))
                                ->action(function ($set, $get): void {
                                    $token = $get('telegram_token');
                                    $found = false;

                                    try {
                                        $updates = TelegramUpdates::create()
                                            ->options([
                                                'timeout' => 0,
                                                'limit' => 100,
                                            ])
                                            ->get();

                                        if (! empty($updates['ok']) && ! empty($updates['result'])) {
                                            foreach (array_reverse($updates['result']) as $message) {
                                                $text = $message['message']['text'] ?? null;
                                                if (! $text) {
                                                    continue;
                                                }
                                                if ($text === '/start ' . $token || $text === '/start') {
                                                    $chatId = $message['message']['chat']['id'] ?? null;
                                                    if ($chatId) {
                                                        $set('telegram_id', (string) $chatId);
                                                        $found = true;
                                                        Notification::make()
                                                            ->title(__('kit::admin.telegram_id_received'))
                                                            ->success()
                                                            ->send();

                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    } catch (\Throwable $e) {
                                        Notification::make()
                                            ->title(__('kit::admin.telegram_id_error'))
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();

                                        return;
                                    }

                                    if (! $found) {
                                        Notification::make()
                                            ->title(__('kit::admin.telegram_id_not_found'))
                                            ->body(__('kit::admin.telegram_id_not_found_hint'))
                                            ->warning()
                                            ->send();
                                    }
                                })
                                ->icon('heroicon-o-arrow-path')
                                ->color('success'),
                        ])
                        ->compact()
                        ->footerActionsAlignment(Alignment::End),
                ])->columns(1),
                Tab::make(__('kit::admin.password'))->schema([
                    TextInput::make('old_password')
                        ->label(__('kit::admin.current_password'))
                        ->password()
                        ->required(fn ($get): bool => filled($get('password')))
                        ->dehydrated(false)
                        ->rule(fn (): \Closure => function ($attribute, $value, $fail): void {
                            /**
                             * @var Admin $user
                             */
                            $user = auth()->user();
                            if (! Hash::check($value, $user->password)) {
                                $fail(__('kit::admin.current_password_incorrect'));
                            }
                        }),
                    $this->getPasswordFormComponent(),
                    $this->getPasswordConfirmationFormComponent(),
                ])->columns(1),
                Tab::make(__('kit::admin.notifications'))->schema([
                    Section::make(__('kit::admin.mail_notifications'))->schema($this->getNotificationsSchema('mail'))->columns(2),
                    Section::make(__('kit::admin.telegram_notifications'))->schema($this->getNotificationsSchema('telegram'))->columns(2),
                ])->columns(1),
            ]),
        ]);
    }

    public function getNotificationsSchema($channel): array
    {
        return collect(config('kit.notifications'))->map(fn ($value, $key): Toggle => Toggle::make("notifications.{$channel}.{$key}")->label(__($value))->default(true))->toArray();
    }

    public function afterSave(): void
    {
        if ($this->getUser()?->locale != App::getLocale()) {
            $this->redirect(url: Profile::getUrl());
        }
    }
}

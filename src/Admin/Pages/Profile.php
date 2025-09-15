<?php

namespace SmartCms\Kit\Admin\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
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
                    TextInput::make('telegram_token')->disabled()->hidden()->formatStateUsing(function ($get) {
                        return \Illuminate\Support\Str::random(32);
                    }),
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
                                ->url(function ($get) {
                                    $token = $get('telegram_token');
                                    $botUsername = setting('telegram.bot_username');
                                    $botUsername = str_replace('@', '', $botUsername);
                                    $url = "https://t.me/{$botUsername}?start={$token}";

                                    return $url;
                                })
                                ->openUrlInNewTab(),
                            Action::make('get_telegram_id')
                                ->label(__('kit::admin.get_telegram_id'))
                                ->action(function ($set, $get) {
                                    $token = $get('telegram_token');
                                    $updates = TelegramUpdates::create()
                                        ->latest()
                                        ->limit(5)
                                        ->options([
                                            'timeout' => 0,
                                        ])
                                        ->get();
                                    if ($updates['ok']) {
                                        $messages = $updates['result'];
                                        foreach ($messages as $message) {
                                            if (! isset($message['message']['text'])) {
                                                continue;
                                            }
                                            $text = $message['message']['text'];
                                            if ($text == '/start ' . $token) {
                                                $chatId = $message['message']['chat']['id'];
                                                $set('telegram_id', $chatId);

                                                break;
                                            }
                                        }
                                    }
                                })
                                ->openUrlInNewTab()
                                ->icon('heroicon-o-arrow-path')
                                ->color('success'),
                        ])
                        ->compact()
                        ->footerActionsAlignment(Alignment::End)
                ])->columns(1),
                Tab::make(__('kit::admin.password'))->schema([
                    TextInput::make('old_password')
                        ->label('Current Password')
                        ->password()
                        ->required(fn($get) => filled($get('password')))
                        ->dehydrated(false) // Do not save to DB
                        ->rule(function () {
                            return function ($attribute, $value, $fail) {
                                /**
                                 * @var Admin $user
                                 */
                                $user = auth()->user();
                                if (! Hash::check($value, $user->password)) {
                                    $fail('The current password is incorrect.');
                                }
                            };
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
        return collect(config('kit.notifications'))->map(function ($value, $key) use ($channel) {
            return Toggle::make("notifications.{$channel}.{$key}")->label(__($value))->default(true);
        })->toArray();
    }

    public function afterSave(): void
    {
        if ($this->getUser()?->locale != App::getLocale()) {
            $this->redirect(url: Profile::getUrl());
        }
    }
}

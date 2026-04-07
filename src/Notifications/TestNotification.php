<?php

namespace SmartCms\Kit\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class TestNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(public string $type) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $via = [];
        if ($this->type === 'telegram' && $notifiable->telegram_id) {
            $via[] = 'telegram';
        }
        if ($this->type === 'mail') {
            $via[] = 'mail';
        }

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line(__('kit::admin.test_notification_message'));
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::create()
            ->to($notifiable->telegram_id)
            ->content(__('kit::admin.test_notification_message'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

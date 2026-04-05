<?php

namespace SmartCms\Kit\Notifications;

use Filament\Notifications\Notification as NotificationsNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;
use SmartCms\Forms\Admin\Resources\ContactForms\ContactFormResource;
use SmartCms\Forms\Models\ContactForm;

class NewContactFormNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(public ContactForm $form) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $via = ['database'];
        $notificationSettings = $notifiable->notifications ?? [];
        if (isset($notificationSettings['mail']) && isset($notificationSettings['mail']['new_contact_form']) && $notificationSettings['mail']['new_contact_form']) {
            $via[] = 'mail';
        }
        if (isset($notificationSettings['telegram']) && isset($notificationSettings['telegram']['new_contact_form']) && $notificationSettings['telegram']['new_contact_form'] && $notifiable->telegram_id) {
            $via[] = 'telegram';
        }

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $formName = $this->form->prefix;
        $message = (new MailMessage)
            ->line(__('kit::admin.new_contact_form_submission'))
            ->line(__('kit::admin.form') . ": {$formName}");

        if ($this->form->data && is_array($this->form->data)) {
            foreach ($this->form->data as $key => $value) {
                $key = ucfirst(str_replace('_', ' ', $key));
                $value = is_array($value) ? implode(', ', $value) : $value;
                $message->line("{$key}: {$value}");
            }
        }

        return $message->action(__('kit::admin.view_in_admin'), ContactFormResource::getUrl());
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $formName = $this->form->prefix;

        return TelegramMessage::create()
            ->to($notifiable->telegram_id)
            ->content(__('kit::admin.new_contact_form_submission') . "\n\n" . __('kit::admin.form') . ": {$formName}")
            ->button(__('kit::admin.view_in_admin'), ContactFormResource::getUrl());
    }

    public function toDatabase(object $notifiable): array
    {
        $title = __('kit::admin.form') . ' ' . $this->form->prefix . ' ' . __('kit::admin.was_sent');
        $notification = NotificationsNotification::make()->title($title)->success()->toDatabase();
        $notifiable->notifyNow($notification);

        return [];
    }
}

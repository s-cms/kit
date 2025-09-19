<?php

namespace SmartCms\Kit\Actions\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsAction;

class BindConfig
{
    use AsAction;

    public function handle(): void
    {
        if (! Schema::hasTable(config('settings.database_table_name', 'settings'))) {
            return;
        }
        $this->bindMailer();
        $this->bindTelegram();
        $this->bindName();
    }

    public function bindMailer(): void
    {
        $mailConfig = [
            'transport' => 'smtp',
            'scheme' => 'smtp',
            'host' => app('s')->get('mail.host', ''),
            'port' => app('s')->get('mail.port', ''),
            'username' => app('s')->get('mail.username', ''),
            'password' => app('s')->get('mail.password', ''),
            'timeout' => 15,
            'encryption' => app('s')->get('mail.encryption', ''),
        ];
        $mailFrom = [
            'address' => app('s')->get('mail.from', ''),
            'name' => app('s')->get('mail.name', ''),
        ];
        $provider = app('s')->get('mail.provider', 'sendmail');
        Config::set('mail.mailers.admin_scms', $mailConfig);
        Config::set('mail.from', $mailFrom);
        if ($provider == 'smtp') {
            Config::set('mail.default', 'admin_scms');
        } else {
            Config::set('mail.default', 'sendmail');
        }
    }

    public function bindTelegram(): void
    {
        Config::set('services.telegram-bot-api.token', app('s')->get('telegram.token', ''));
    }

    public function bindName(): void
    {
        Config::set('app.name', company_name());
    }
}

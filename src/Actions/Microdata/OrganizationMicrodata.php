<?php

namespace SmartCms\Kit\Actions\Microdata;

use Lorisleiva\Actions\Concerns\AsAction;

class OrganizationMicrodata
{
    use AsAction;

    public function handle(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => company_name(),
            'url' => url('/'),
            'logo' => logo()['src'] ?? '',
        ];
    }
}

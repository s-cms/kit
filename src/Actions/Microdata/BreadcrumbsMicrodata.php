<?php

namespace SmartCms\Kit\Actions\Microdata;

use Lorisleiva\Actions\Concerns\AsAction;

class BreadcrumbsMicrodata
{
    use AsAction;

    public function handle(array $breadcrumbs = []): array
    {
        $microdata = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => hostname(),
                    'item' => host(),
                ],
            ],
        ];
        $position = 1;
        foreach ($breadcrumbs as $key => $breadcrumb) {
            if ($breadcrumb == host()) {
                continue;
            }
            $position++;
            $microdata['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $key,
                'item' => $breadcrumb,
            ];
        }

        return $microdata;
    }
}

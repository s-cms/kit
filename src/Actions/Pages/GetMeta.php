<?php

namespace SmartCms\Kit\Actions\Pages;

use SmartCms\Kit\Actions\Microdata\BreadcrumbsMicrodata;
use SmartCms\Kit\Http\Resources\MediaResource;
use SmartCms\Kit\Models\Front\FrontPage;

class GetMeta
{
    public static function run(FrontPage $page): array
    {
        return new self()->handle($page);
    }

    public function handle(FrontPage $page): array
    {
        return [
            'microdata' => $this->getMicrodata($page),
            'meta' => $this->getMetaData($page),
            'links' => $this->getLinks($page),
        ];
    }

    protected function getMicrodata(FrontPage $page): array
    {
        $formattedBreadcrumbs = collect($page->getBreadcrumbs())->mapWithKeys(function ($value, $key): array {
            return [$value['name'] => $value['url']['url']];
        });
        app('microdata')->add(BreadcrumbsMicrodata::make()->handle($formattedBreadcrumbs->toArray()));

        return app('microdata')->get();
    }

    protected function getMetadata(FrontPage $page): array
    {
        $ogImage = $page?->image;
        if (blank($ogImage)) {
            $ogImage = app('s')->get('og_image', null);
        }
        $ogImage = MediaResource::make($ogImage)->toArray(request())['src'] ?? '';
        $twitterUsername = app('s')->get('branding.twitter_name') ?? '';
        if (blank($twitterUsername)) {
            $twitterUsername = company_name();
        }
        if (! str_contains($twitterUsername, '@')) {
            $twitterUsername = '@' . $twitterUsername;
        }
        $titlePrefix = app('s')->get('title.prefix') ?? '';
        $titleSuffix = app('s')->get('title.suffix') ?? '';
        $descriptionPrefix = app('s')->get('description.prefix') ?? '';
        $descriptionSuffix = app('s')->get('description.suffix') ?? '';
        $canonical = url()->current();
        $title = $titlePrefix . $page->title . $titleSuffix;
        $description = $descriptionPrefix . $page->description . $descriptionSuffix;

        return [
            'title' => $title,
            'description' => $description,
            'robots' => $page->is_index ? 'index, follow' : 'noindex, nofollow',
            'image' => $ogImage,
            'og:type' => 'website',
            'og:title' => $title,
            'og:description' => $description,
            'og:url' => $canonical,
            'og:image' => $ogImage,
            'og:locale' => app()->getLocale(),
            'og:site_name' => company_name(),
            'twitter:card' => 'summary',
            'twitter:site' => $twitterUsername,
            'twitter:title' => $title,
            'twitter:description' => $description,
        ];
    }

    protected function getLinks(FrontPage $page): array
    {
        return [
            'canonical' => url()->current(),
            'alternate' => collect(language_routes())
                ->map(function ($lang) {
                    return [
                        'hreflang' => $lang['code'] == main_lang() ? 'x-default' : $lang['code'],
                        'href' => $lang['route'],
                    ];
                })
                ->toArray(),
        ];
    }
}

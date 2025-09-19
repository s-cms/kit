<?php

namespace SmartCms\Kit\Http\Handlers;

use Illuminate\Support\Facades\Context;
use Lorisleiva\Actions\Concerns\AsAction;
use SmartCms\Kit\Models\Page;
use Symfony\Component\HttpKernel\Attribute\Cache;

class SitemapHandler
{
    private array $replace = [
        '/<!--[\s\S]*?-->/' => '', // remove comments
        "/<\?php/" => '<?php ',
        "/\n([\S])/" => '$1',
        "/\r/" => '', // remove carriage return
        "/\n/" => '', // remove new lines
        "/\t/" => '', // remove tab
        "/\s+/" => ' ', // remove spaces
        '/> +</' => '><',
    ];

    use AsAction;

    #[Cache(public: true, maxage: 3600, mustRevalidate: true)]
    public function handle()
    {
        $lang = request('lang', null);
        if (! $lang) {
            return $this->renderSitemap();
        }
        app()->setLocale($lang);
        Context::add('current_lang', $lang);
        app('lang')->setCurrent($lang);
        $links = [];
        foreach (Page::query()->where('is_index', true)->get() as $page) {
            $links[] = [
                'link' => $page->route(),
                'priority' => 0.7,
                'changefreq' => 'weekly',
                'lastmod' => $page->published_at ?? $page->created_at,
            ];
        }

        return response(view('kit::sitemap', [
            'links' => $links,
        ]))->header('Content-Type', 'text/xml');
    }

    public function renderSitemap()
    {
        return response(view('kit::sitemaps'))->header('Content-Type', 'text/xml');
    }
}

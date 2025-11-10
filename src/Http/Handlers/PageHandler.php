<?php

namespace SmartCms\Kit\Http\Handlers;

use Illuminate\Http\Request;
use SmartCms\Kit\Models\Front\FrontPage;
use Symfony\Component\HttpKernel\Attribute\Cache;

class PageHandler
{
    #[Cache(public: true, maxage: 31536000, mustRevalidate: true)]
    public function __invoke(Request $request, string $path = ''): string
    {
        // Get max depth from config (default 5)
        $maxDepth = config('kit.max_page_depth', 5);

        // Split path into segments
        $segments = array_filter(explode('/', $path));

        // Remove language segment if present
        $segments = array_values(array_filter($segments, fn ($value) => $value != current_lang()));

        // Validate depth doesn't exceed max
        if (count($segments) > $maxDepth) {
            return abort(404);
        }

        // Find page by hierarchical path
        $page = $this->findPage($segments);

        return $page?->render() ?? abort(404);
    }

    protected function findPage(array $segments, $parentId = null)
    {
        $slug = array_shift($segments);
        $page = FrontPage::query()->where('slug', $slug ?? '')
            ->where('parent_id', $parentId)
            ->first();
        if (! $page) {
            return null;
        }
        if (count($segments) > 0) {
            return $this->findPage($segments, $page->id);
        }

        return $page;
    }
}

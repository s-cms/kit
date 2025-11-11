<?php

namespace SmartCms\Kit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use SmartCms\Kit\Models\Page;

class PreviewController
{
    /**
     * Handle preview request with anti-indexing headers.
     *
     * @param  string  $token  The preview token
     */
    public function __invoke(string $token): Response
    {
        // Validate token and get page ID
        $pageId = Cache::get("preview.{$token}");

        if (! $pageId) {
            abort(404, __('kit::admin.preview_link_expired'));
        }

        $page = Page::find($pageId);

        if (! $page) {
            abort(404, __('kit::admin.page_not_found'));
        }

        // Only allow preview for non-published pages
        if ($page->status?->value === 'published') {
            // Redirect to the actual page if it's already published
            return redirect()->to($page->route());
        }

        // Render the page with anti-indexing headers (Layer 1: HTTP Headers)
        return response($page->render())
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}

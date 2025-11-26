<?php

namespace SmartCms\Kit\Services\SEO;

use SmartCms\Kit\Models\Media;
use SmartCms\Kit\Models\Page;

/**
 * Social Media Preview Service
 *
 * Generates previews of how content will appear when shared on social media platforms
 */
class SocialMediaPreview
{
    protected string $title;

    protected ?string $description = null;

    protected ?string $image = null;

    protected ?string $url = null;

    protected ?string $imageWidth = null;

    protected ?string $imageHeight = null;

    public function __construct(
        protected Page $page,
        protected ?string $lang = null
    ) {
        $this->lang = $lang ?? main_lang();
        $this->extractDataFromPage();
    }

    /**
     * Extract data from Page model and populate properties
     */
    protected function extractDataFromPage(): void
    {
        $this->title = $this->page->getTranslation('title', $this->lang) ?? $this->page->getTranslation('name', $this->lang);
        $this->description = $this->page->getTranslation('description', $this->lang);
        $this->url = $this->page->route();

        // Get featured image from media library
        $featuredImage = Media::query()->find($this->page->image ?? $this->page->banner ?? null);
        if ($featuredImage) {
            $this->image = $featuredImage->getUrl();
            $this->imageWidth = $featuredImage->width ?? 0;
            $this->imageHeight = $featuredImage->height ?? 0;
        }
    }

    /**
     * Generate all social media previews
     */
    public function generatePreviews(): array
    {
        return [
            'google' => $this->generateGooglePreview(),
            'facebook' => $this->generateFacebookPreview(),
            'twitter' => $this->generateTwitterPreview(),
            'linkedin' => $this->generateLinkedInPreview(),
        ];
    }

    /**
     * Generate Google Search Result Preview
     */
    protected function generateGooglePreview(): array
    {
        // Google limits
        $titleLimit = 60;
        $descriptionLimit = 160;

        return [
            'title' => $this->title,
            'title_length' => mb_strlen($this->title),
            'title_limit' => $titleLimit,
            'title_status' => $this->getStatus(mb_strlen($this->title), $titleLimit),
            'description' => $this->description,
            'description_length' => mb_strlen($this->description ?? ''),
            'description_limit' => $descriptionLimit,
            'description_status' => $this->getStatus(mb_strlen($this->description ?? ''), $descriptionLimit),
            'url' => $this->url,
            'display_url' => $this->url ? (parse_url($this->url, PHP_URL_HOST) . parse_url($this->url, PHP_URL_PATH)) : '',
        ];
    }

    /**
     * Generate Facebook Open Graph Preview
     */
    protected function generateFacebookPreview(): array
    {
        // Facebook limits
        $titleLimit = 60;
        $descriptionLimit = 200;

        // Get image dimensions if available
        $imageDimensions = null;
        if ($this->imageWidth && $this->imageHeight) {
            $imageDimensions = "{$this->imageWidth}x{$this->imageHeight}";
        }

        return [
            'title' => $this->title,
            'title_length' => mb_strlen($this->title),
            'title_limit' => $titleLimit,
            'title_status' => $this->getStatus(mb_strlen($this->title), $titleLimit),
            'description' => $this->description,
            'description_length' => mb_strlen($this->description ?? ''),
            'description_limit' => $descriptionLimit,
            'description_status' => $this->getStatus(mb_strlen($this->description ?? ''), $descriptionLimit),
            'image' => $this->image,
            'image_dimensions' => $imageDimensions,
            'image_status' => $this->image ? 'valid' : 'missing',
            'url' => $this->url,
            'domain' => $this->url ? parse_url($this->url, PHP_URL_HOST) : '',
            'site_name' => config('app.name'),
        ];
    }

    /**
     * Generate Twitter Card Preview
     */
    protected function generateTwitterPreview(): array
    {
        // Twitter limits
        $titleLimit = 70;
        $descriptionLimit = 200;

        return [
            'title' => $this->title,
            'title_length' => mb_strlen($this->title),
            'title_limit' => $titleLimit,
            'title_status' => $this->getStatus(mb_strlen($this->title), $titleLimit),
            'description' => $this->description,
            'description_length' => mb_strlen($this->description ?? ''),
            'description_limit' => $descriptionLimit,
            'description_status' => $this->getStatus(mb_strlen($this->description ?? ''), $descriptionLimit),
            'image' => $this->image,
            'image_status' => $this->image ? 'valid' : 'missing',
            'url' => $this->url,
            'domain' => $this->url ? parse_url($this->url, PHP_URL_HOST) : '',
            'card_type' => $this->image ? 'summary_large_image' : 'summary',
        ];
    }

    /**
     * Generate LinkedIn Preview
     */
    protected function generateLinkedInPreview(): array
    {
        // LinkedIn limits (similar to Facebook)
        $titleLimit = 60;
        $descriptionLimit = 200;

        return [
            'title' => $this->title,
            'title_length' => mb_strlen($this->title),
            'title_limit' => $titleLimit,
            'title_status' => $this->getStatus(mb_strlen($this->title), $titleLimit),
            'description' => $this->description,
            'description_length' => mb_strlen($this->description ?? ''),
            'description_limit' => $descriptionLimit,
            'description_status' => $this->getStatus(mb_strlen($this->description ?? ''), $descriptionLimit),
            'image' => $this->image,
            'image_status' => $this->image ? 'valid' : 'missing',
            'url' => $this->url,
            'domain' => $this->url ? parse_url($this->url, PHP_URL_HOST) : '',
        ];
    }

    /**
     * Get status based on length
     */
    protected function getStatus(int $length, int $limit): string
    {
        if ($length === 0) {
            return 'empty';
        }

        if ($length <= $limit) {
            return 'good';
        }

        if ($length <= $limit * 1.1) {
            return 'warning';
        }

        return 'error';
    }

    /**
     * Format previews as text for modal display
     */
    public function formatAsText(array $previews): array
    {
        $output = [];

        foreach ($previews as $platform => $preview) {
            $output[$platform] = $this->formatPlatformPreview($platform, $preview);
        }

        return $output;
    }

    /**
     * Format individual platform preview
     */
    protected function formatPlatformPreview(string $platform, array $data): string
    {
        return match ($platform) {
            'google' => $this->formatGooglePreview($data),
            'facebook' => $this->formatFacebookPreview($data),
            'twitter' => $this->formatTwitterPreview($data),
            'linkedin' => $this->formatLinkedInPreview($data),
            default => '',
        };
    }

    /**
     * Format Google Search Result Preview
     */
    protected function formatGooglePreview(array $data): string
    {
        $title = htmlspecialchars($this->truncateForDisplay($data['title'], $data['title_limit']), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($this->truncateForDisplay($data['description'] ?? 'No description set', $data['description_limit']), ENT_QUOTES, 'UTF-8');
        $displayUrl = htmlspecialchars($data['display_url'], ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($data['url'] ?? '', ENT_QUOTES, 'UTF-8');

        $titleStatusClass = $this->getStatusClass($data['title_status']);
        $descStatusClass = $this->getStatusClass($data['description_status']);

        return <<<HTML
<div style="font-family: arial, sans-serif; max-width: 600px; padding: 20px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px;">
    <div style="margin-bottom: 20px;">
        <div style="font-size: 12px; color: #70757a; margin-bottom: 3px;">
            {$displayUrl}
        </div>
        <h3 style="margin: 0; padding: 0; font-size: 20px; font-weight: 400; line-height: 1.3; margin-bottom: 3px;">
            <a href="{$url}" style="color: #1a0dab; text-decoration: none; cursor: pointer;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                {$title}
            </a>
        </h3>
        <div style="font-size: 14px; line-height: 1.58; color: #4d5156; margin-top: 3px;">
            {$description}
        </div>
    </div>

    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e0e0e0; font-size: 12px; color: #70757a;">
        <div style="margin-bottom: 8px;">
            <strong>Title:</strong>
            <span class="{$titleStatusClass}" style="display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                {$data['title_length']}/{$data['title_limit']} chars
            </span>
        </div>
        <div>
            <strong>Description:</strong>
            <span class="{$descStatusClass}" style="display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                {$data['description_length']}/{$data['description_limit']} chars
            </span>
        </div>
    </div>

    <style>
        .status-good { background-color: #e8f5e9; color: #2e7d32; }
        .status-warning { background-color: #fff3e0; color: #e65100; }
        .status-error { background-color: #ffebee; color: #c62828; }
        .status-empty { background-color: #f5f5f5; color: #616161; }
    </style>
</div>
HTML;
    }

    /**
     * Get CSS class for status
     */
    protected function getStatusClass(string $status): string
    {
        return match ($status) {
            'good' => 'status-good',
            'warning' => 'status-warning',
            'error' => 'status-error',
            'empty' => 'status-empty',
            default => 'status-empty',
        };
    }

    /**
     * Format Facebook Open Graph Preview
     */
    protected function formatFacebookPreview(array $data): string
    {
        $title = htmlspecialchars($this->truncateForDisplay($data['title'], $data['title_limit']), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($this->truncateForDisplay($data['description'] ?? 'No description set', $data['description_limit']), ENT_QUOTES, 'UTF-8');
        $domain = htmlspecialchars($data['domain'], ENT_QUOTES, 'UTF-8');
        $siteName = htmlspecialchars($data['site_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $imageUrl = htmlspecialchars($data['image'] ?? '', ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($data['url'] ?? '', ENT_QUOTES, 'UTF-8');

        $titleStatusClass = $this->getStatusClass($data['title_status']);
        $descStatusClass = $this->getStatusClass($data['description_status']);
        $imageStatusClass = $data['image_status'] === 'valid' ? 'status-good' : 'status-empty';
        $imageDimensions = ($data['image_dimensions'] ?? '') ? htmlspecialchars($data['image_dimensions'], ENT_QUOTES, 'UTF-8') : '';
        $imageDimensionsHtml = $imageDimensions ? "<span style=\"margin-left: 8px; color: #606770;\">({$imageDimensions})</span>" : '';
        $imageStatusText = $data['image_status'] === 'valid' ? 'Set' : 'Missing';

        $imageHtml = '';
        if ($imageUrl) {
            $imageHtml = <<<HTML
            <div style="width: 100%; height: 315px; background: #f0f2f5; border-top-left-radius: 8px; border-top-right-radius: 8px; overflow: hidden; margin-bottom: 12px;">
                <img src="{$imageUrl}" alt="{$title}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#8a8d91;font-size:14px;\'>No image available</div>';">
            </div>
HTML;
        } else {
            $imageHtml = <<<'HTML'
            <div style="width: 100%; height: 315px; background: #f0f2f5; border-top-left-radius: 8px; border-top-right-radius: 8px; display: flex; align-items: center; justify-content: center; color: #8a8d91; font-size: 14px; margin-bottom: 12px;">
                No image available
            </div>
HTML;
        }

        return <<<HTML
<div style="font-family: Helvetica, Arial, sans-serif; max-width: 500px; background: #fff; border: 1px solid #dadde1; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
    {$imageHtml}
    <div style="padding: 12px;">
        <div style="font-size: 12px; color: #606770; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 0.2px;">
            {$domain}
        </div>
        <a href="{$url}" style="text-decoration: none; color: #050505; display: block; margin-bottom: 5px;">
            <div style="font-size: 16px; font-weight: 600; line-height: 1.38; color: #050505;">
                {$title}
            </div>
        </a>
        <div style="font-size: 14px; line-height: 1.33; color: #606770; margin-top: 3px;">
            {$description}
        </div>
    </div>

    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e4e6eb; padding: 12px; font-size: 12px; color: #606770; background: #f0f2f5;">
        <div style="margin-bottom: 8px;">
            <strong>Title:</strong>
            <span class="{$titleStatusClass}" style="display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                {$data['title_length']}/{$data['title_limit']} chars
            </span>
        </div>
        <div style="margin-bottom: 8px;">
            <strong>Description:</strong>
            <span class="{$descStatusClass}" style="display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                {$data['description_length']}/{$data['description_limit']} chars
            </span>
        </div>
        <div>
            <strong>Image:</strong>
            <span class="{$imageStatusClass}" style="display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                {$imageStatusText}
            </span>
            {$imageDimensionsHtml}
        </div>
    </div>

    <style>
        .status-good { background-color: #e8f5e9; color: #2e7d32; }
        .status-warning { background-color: #fff3e0; color: #e65100; }
        .status-error { background-color: #ffebee; color: #c62828; }
        .status-empty { background-color: #f5f5f5; color: #616161; }
    </style>
</div>
HTML;
    }

    /**
     * Format Twitter Card Preview
     */
    protected function formatTwitterPreview(array $data): string
    {
        $title = htmlspecialchars($this->truncateForDisplay($data['title'], $data['title_limit']), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($this->truncateForDisplay($data['description'] ?? 'No description set', $data['description_limit']), ENT_QUOTES, 'UTF-8');
        $domain = htmlspecialchars($data['domain'], ENT_QUOTES, 'UTF-8');
        $imageUrl = htmlspecialchars($data['image'] ?? '', ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($data['url'] ?? '', ENT_QUOTES, 'UTF-8');
        $cardType = htmlspecialchars($data['card_type'] ?? 'summary', ENT_QUOTES, 'UTF-8');

        $titleStatusClass = $this->getStatusClass($data['title_status']);
        $descStatusClass = $this->getStatusClass($data['description_status']);
        $imageStatusClass = $data['image_status'] === 'valid' ? 'status-good' : 'status-empty';
        $imageStatusText = $data['image_status'] === 'valid' ? 'Set' : 'Missing';

        $isLargeImage = $cardType === 'summary_large_image';
        $imageHeight = $isLargeImage ? '262px' : '120px';

        $imageHtml = '';
        if ($imageUrl) {
            $imageHtml = <<<HTML
            <div style="width: 100%; height: {$imageHeight}; background: #000; overflow: hidden; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <img src="{$imageUrl}" alt="{$title}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#fff;font-size:14px;\'>No image available</div>';">
            </div>
HTML;
        } else {
            $imageHtml = <<<HTML
            <div style="width: 100%; height: {$imageHeight}; background: #1d9bf0; border-top-left-radius: 16px; border-top-right-radius: 16px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px;">
                No image available
            </div>
HTML;
        }

        return <<<HTML
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; max-width: 500px; background: #000; border-radius: 16px; overflow: hidden; box-shadow: rgba(255, 255, 255, 0.2) 0px 0px 15px, rgba(255, 255, 255, 0.15) 0px 0px 3px 1px;">
    {$imageHtml}
    <div style="padding: 12px; background: #000;">
        <div style="font-size: 15px; color: #fff; font-weight: 700; line-height: 1.3125; margin-bottom: 2px;">
            {$title}
        </div>
        <div style="font-size: 15px; color: #8b98a5; line-height: 1.3125; margin-top: 2px; margin-bottom: 12px;">
            {$description}
        </div>
        <div style="display: flex; align-items: center; margin-top: 12px;">
            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: #8b98a5; margin-right: 4px;">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path>
            </svg>
            <span style="font-size: 15px; color: #8b98a5;">{$domain}</span>
        </div>
    </div>

    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #2f3336; padding: 12px; font-size: 12px; color: #8b98a5; background: #16181c;">
        <div style="margin-bottom: 8px;">
            <strong style="color: #fff;">Title:</strong>
            <span class="{$titleStatusClass}" style="display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                {$data['title_length']}/{$data['title_limit']} chars
            </span>
        </div>
        <div style="margin-bottom: 8px;">
            <strong style="color: #fff;">Description:</strong>
            <span class="{$descStatusClass}" style="display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                {$data['description_length']}/{$data['description_limit']} chars
            </span>
        </div>
        <div>
            <strong style="color: #fff;">Image:</strong>
            <span class="{$imageStatusClass}" style="display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                {$imageStatusText}
            </span>
            <span style="margin-left: 8px; color: #8b98a5;">({$cardType})</span>
        </div>
    </div>

    <style>
        .status-good { background-color: #e8f5e9; color: #2e7d32; }
        .status-warning { background-color: #fff3e0; color: #e65100; }
        .status-error { background-color: #ffebee; color: #c62828; }
        .status-empty { background-color: #f5f5f5; color: #616161; }
    </style>
</div>
HTML;
    }

    /**
     * Format LinkedIn Preview
     */
    protected function formatLinkedInPreview(array $data): string
    {
        $title = htmlspecialchars($this->truncateForDisplay($data['title'], $data['title_limit']), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($this->truncateForDisplay($data['description'] ?? 'No description set', $data['description_limit']), ENT_QUOTES, 'UTF-8');
        $domain = htmlspecialchars($data['domain'], ENT_QUOTES, 'UTF-8');
        $imageUrl = htmlspecialchars($data['image'] ?? '', ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($data['url'] ?? '', ENT_QUOTES, 'UTF-8');

        $titleStatusClass = $this->getStatusClass($data['title_status']);
        $descStatusClass = $this->getStatusClass($data['description_status']);
        $imageStatusClass = $data['image_status'] === 'valid' ? 'status-good' : 'status-empty';
        $imageStatusText = $data['image_status'] === 'valid' ? 'Set' : 'Missing';

        $imageHtml = '';
        if ($imageUrl) {
            $imageHtml = <<<HTML
            <div style="width: 100%; height: 314px; background: #f3f2ef; overflow: hidden; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                <img src="{$imageUrl}" alt="{$title}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#666;font-size:14px;\'>No image available</div>';">
            </div>
HTML;
        } else {
            $imageHtml = <<<'HTML'
            <div style="width: 100%; height: 314px; background: #f3f2ef; border-top-left-radius: 8px; border-top-right-radius: 8px; display: flex; align-items: center; justify-content: center; color: #666; font-size: 14px;">
                No image available
            </div>
HTML;
        }

        return <<<HTML
<div style="font-family: -apple-system, system-ui, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', 'Fira Sans', Ubuntu, Oxygen, 'Oxygen Sans', Cantarell, 'Droid Sans', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Lucida Grande', Helvetica, Arial, sans-serif; max-width: 552px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; box-shadow: 0 0 0 1px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.08);">
    {$imageHtml}
    <div style="padding: 12px;">
        <div style="font-size: 12px; color: rgba(0,0,0,0.6); font-weight: 600; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">
            {$domain}
        </div>
        <a href="{$url}" style="text-decoration: none; color: rgba(0,0,0,0.9); display: block;">
            <div style="font-size: 16px; font-weight: 600; line-height: 1.4; color: rgba(0,0,0,0.9); margin-bottom: 4px;">
                {$title}
            </div>
        </a>
        <div style="font-size: 14px; line-height: 1.4; color: rgba(0,0,0,0.6); margin-top: 4px;">
            {$description}
        </div>
    </div>

    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e0e0e0; padding: 12px; font-size: 12px; color: rgba(0,0,0,0.6); background: #f3f2ef;">
        <div style="margin-bottom: 8px;">
            <strong style="color: rgba(0,0,0,0.9);">Title:</strong>
            <span class="{$titleStatusClass}" style="display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                {$data['title_length']}/{$data['title_limit']} chars
            </span>
        </div>
        <div style="margin-bottom: 8px;">
            <strong style="color: rgba(0,0,0,0.9);">Description:</strong>
            <span class="{$descStatusClass}" style="display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                {$data['description_length']}/{$data['description_limit']} chars
            </span>
        </div>
        <div>
            <strong style="color: rgba(0,0,0,0.9);">Image:</strong>
            <span class="{$imageStatusClass}" style="display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                {$imageStatusText}
            </span>
        </div>
    </div>

    <style>
        .status-good { background-color: #e8f5e9; color: #2e7d32; }
        .status-warning { background-color: #fff3e0; color: #e65100; }
        .status-error { background-color: #ffebee; color: #c62828; }
        .status-empty { background-color: #f5f5f5; color: #616161; }
    </style>
</div>
HTML;
    }

    /**
     * Get status icon
     */
    protected function getStatusIcon(string $status): string
    {
        return match ($status) {
            'good' => '✓',
            'warning' => '⚠',
            'error' => '✗',
            'empty' => '✗',
            default => '•',
        };
    }

    /**
     * Truncate text for display
     */
    protected function truncateForDisplay(?string $text, int $limit): string
    {
        if (! $text) {
            return '[Empty]';
        }

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return mb_substr($text, 0, $limit) . '... [TRUNCATED]';
    }

    /**
     * Get validation messages
     */
    protected function getValidationMessages(array $data): string
    {
        $messages = [];

        // Title validation
        if ($data['title_status'] === 'empty') {
            $messages[] = '✗ Title is empty - required for social sharing';
        } elseif ($data['title_status'] === 'error') {
            $messages[] = "✗ Title is too long ({$data['title_length']} chars) - will be truncated";
        } elseif ($data['title_status'] === 'warning') {
            $messages[] = "⚠ Title is slightly long ({$data['title_length']} chars) - may be truncated";
        } else {
            $messages[] = "✓ Title length is optimal ({$data['title_length']} chars)";
        }

        // Description validation
        if ($data['description_status'] === 'empty') {
            $messages[] = '⚠ Description is empty - recommended for better engagement';
        } elseif ($data['description_status'] === 'error') {
            $messages[] = "✗ Description is too long ({$data['description_length']} chars) - will be truncated";
        } elseif ($data['description_status'] === 'warning') {
            $messages[] = "⚠ Description is slightly long ({$data['description_length']} chars) - may be truncated";
        } else {
            $messages[] = "✓ Description length is optimal ({$data['description_length']} chars)";
        }

        // Image validation
        if (isset($data['image_status'])) {
            if ($data['image_status'] === 'missing') {
                $messages[] = '⚠ No featured image - recommended for better engagement';
            } else {
                $messages[] = '✓ Featured image is set';
            }
        }

        if (count($messages) === 0) {
            return '';
        }

        $output = "VALIDATION\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= implode("\n", $messages) . "\n";

        return $output;
    }
}

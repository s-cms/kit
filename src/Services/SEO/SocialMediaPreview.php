<?php

namespace SmartCms\Kit\Services\SEO;

use SmartCms\Kit\Models\Page;

/**
 * Social Media Preview Service
 *
 * Generates previews of how content will appear when shared on social media platforms
 */
class SocialMediaPreview
{
    public function __construct(protected Page $page) {}

    /**
     * Generate all social media previews
     */
    public function generatePreviews(): array
    {
        $mainLang = main_lang();

        return [
            'google' => $this->generateGooglePreview($mainLang),
            'facebook' => $this->generateFacebookPreview($mainLang),
            'twitter' => $this->generateTwitterPreview($mainLang),
            'linkedin' => $this->generateLinkedInPreview($mainLang),
        ];
    }

    /**
     * Generate Google Search Result Preview
     */
    protected function generateGooglePreview(string $lang): array
    {
        $title = $this->page->getTranslation('title', $lang) ?? $this->page->getTranslation('name', $lang);
        $description = $this->page->getTranslation('description', $lang);
        $url = $this->page->getUrl();

        // Google limits
        $titleLimit = 60;
        $descriptionLimit = 160;

        return [
            'title' => $title,
            'title_length' => mb_strlen($title),
            'title_limit' => $titleLimit,
            'title_status' => $this->getStatus(mb_strlen($title), $titleLimit),
            'description' => $description,
            'description_length' => mb_strlen($description ?? ''),
            'description_limit' => $descriptionLimit,
            'description_status' => $this->getStatus(mb_strlen($description ?? ''), $descriptionLimit),
            'url' => $url,
            'display_url' => parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH),
        ];
    }

    /**
     * Generate Facebook Open Graph Preview
     */
    protected function generateFacebookPreview(string $lang): array
    {
        $title = $this->page->getTranslation('title', $lang) ?? $this->page->getTranslation('name', $lang);
        $description = $this->page->getTranslation('description', $lang);
        $image = $this->page->featuredImage?->getUrl('large');
        $url = $this->page->getUrl();

        // Facebook limits
        $titleLimit = 60;
        $descriptionLimit = 200;

        // Get image dimensions if available
        $imageDimensions = null;
        if ($this->page->featuredImage) {
            $width = $this->page->featuredImage->getCustomProperty('width');
            $height = $this->page->featuredImage->getCustomProperty('height');
            if ($width && $height) {
                $imageDimensions = "{$width}x{$height}";
            }
        }

        return [
            'title' => $title,
            'title_length' => mb_strlen($title),
            'title_limit' => $titleLimit,
            'title_status' => $this->getStatus(mb_strlen($title), $titleLimit),
            'description' => $description,
            'description_length' => mb_strlen($description ?? ''),
            'description_limit' => $descriptionLimit,
            'description_status' => $this->getStatus(mb_strlen($description ?? ''), $descriptionLimit),
            'image' => $image,
            'image_dimensions' => $imageDimensions,
            'image_status' => $image ? 'valid' : 'missing',
            'url' => $url,
            'domain' => parse_url($url, PHP_URL_HOST),
            'site_name' => config('app.name'),
        ];
    }

    /**
     * Generate Twitter Card Preview
     */
    protected function generateTwitterPreview(string $lang): array
    {
        $title = $this->page->getTranslation('title', $lang) ?? $this->page->getTranslation('name', $lang);
        $description = $this->page->getTranslation('description', $lang);
        $image = $this->page->featuredImage?->getUrl('large');
        $url = $this->page->getUrl();

        // Twitter limits
        $titleLimit = 70;
        $descriptionLimit = 200;

        return [
            'title' => $title,
            'title_length' => mb_strlen($title),
            'title_limit' => $titleLimit,
            'title_status' => $this->getStatus(mb_strlen($title), $titleLimit),
            'description' => $description,
            'description_length' => mb_strlen($description ?? ''),
            'description_limit' => $descriptionLimit,
            'description_status' => $this->getStatus(mb_strlen($description ?? ''), $descriptionLimit),
            'image' => $image,
            'image_status' => $image ? 'valid' : 'missing',
            'url' => $url,
            'domain' => parse_url($url, PHP_URL_HOST),
            'card_type' => $image ? 'summary_large_image' : 'summary',
        ];
    }

    /**
     * Generate LinkedIn Preview
     */
    protected function generateLinkedInPreview(string $lang): array
    {
        $title = $this->page->getTranslation('title', $lang) ?? $this->page->getTranslation('name', $lang);
        $description = $this->page->getTranslation('description', $lang);
        $image = $this->page->featuredImage?->getUrl('large');
        $url = $this->page->getUrl();

        // LinkedIn limits (similar to Facebook)
        $titleLimit = 60;
        $descriptionLimit = 200;

        return [
            'title' => $title,
            'title_length' => mb_strlen($title),
            'title_limit' => $titleLimit,
            'title_status' => $this->getStatus(mb_strlen($title), $titleLimit),
            'description' => $description,
            'description_length' => mb_strlen($description ?? ''),
            'description_limit' => $descriptionLimit,
            'description_status' => $this->getStatus(mb_strlen($description ?? ''), $descriptionLimit),
            'image' => $image,
            'image_status' => $image ? 'valid' : 'missing',
            'url' => $url,
            'domain' => parse_url($url, PHP_URL_HOST),
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
        $output = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= "🔍 GOOGLE SEARCH RESULT PREVIEW\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // URL
        $output .= "📌 {$data['display_url']}\n\n";

        // Title
        $titleStatus = $this->getStatusIcon($data['title_status']);
        $output .= "{$titleStatus} TITLE ({$data['title_length']}/{$data['title_limit']} chars)\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= $this->truncateForDisplay($data['title'], $data['title_limit']) . "\n\n";

        // Description
        $descStatus = $this->getStatusIcon($data['description_status']);
        $output .= "{$descStatus} DESCRIPTION ({$data['description_length']}/{$data['description_limit']} chars)\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= $this->truncateForDisplay($data['description'] ?? 'No description set', $data['description_limit']) . "\n\n";

        // Validation
        $output .= $this->getValidationMessages($data);

        return $output;
    }

    /**
     * Format Facebook Open Graph Preview
     */
    protected function formatFacebookPreview(array $data): string
    {
        $output = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= "📘 FACEBOOK OPEN GRAPH PREVIEW\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // Image
        $imageStatus = $data['image_status'] === 'valid' ? '✓' : '✗';
        $output .= "{$imageStatus} FEATURED IMAGE\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        if ($data['image']) {
            $output .= "Image URL: {$data['image']}\n";
            if ($data['image_dimensions']) {
                $output .= "Dimensions: {$data['image_dimensions']}\n";
                $output .= "Recommended: 1200x630px\n";
            }
        } else {
            $output .= "⚠ No image set - Facebook will use a default or first image from content\n";
        }
        $output .= "\n";

        // Title
        $titleStatus = $this->getStatusIcon($data['title_status']);
        $output .= "{$titleStatus} TITLE ({$data['title_length']}/{$data['title_limit']} chars)\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= $this->truncateForDisplay($data['title'], $data['title_limit']) . "\n\n";

        // Domain
        $output .= "🌐 DOMAIN\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= "{$data['domain']}\n\n";

        // Description
        $descStatus = $this->getStatusIcon($data['description_status']);
        $output .= "{$descStatus} DESCRIPTION ({$data['description_length']}/{$data['description_limit']} chars)\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= $this->truncateForDisplay($data['description'] ?? 'No description set', $data['description_limit']) . "\n\n";

        // Validation
        $output .= $this->getValidationMessages($data);

        return $output;
    }

    /**
     * Format Twitter Card Preview
     */
    protected function formatTwitterPreview(array $data): string
    {
        $output = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= "🐦 TWITTER CARD PREVIEW\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // Card Type
        $output .= "📋 CARD TYPE: {$data['card_type']}\n\n";

        // Image
        $imageStatus = $data['image_status'] === 'valid' ? '✓' : '✗';
        $output .= "{$imageStatus} FEATURED IMAGE\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        if ($data['image']) {
            $output .= "Image URL: {$data['image']}\n";
            $output .= "Card Type: Large Image (summary_large_image)\n";
            $output .= "Recommended: 1200x628px or larger\n";
        } else {
            $output .= "⚠ No image set - Will use small summary card\n";
        }
        $output .= "\n";

        // Title
        $titleStatus = $this->getStatusIcon($data['title_status']);
        $output .= "{$titleStatus} TITLE ({$data['title_length']}/{$data['title_limit']} chars)\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= $this->truncateForDisplay($data['title'], $data['title_limit']) . "\n\n";

        // Description
        $descStatus = $this->getStatusIcon($data['description_status']);
        $output .= "{$descStatus} DESCRIPTION ({$data['description_length']}/{$data['description_limit']} chars)\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= $this->truncateForDisplay($data['description'] ?? 'No description set', $data['description_limit']) . "\n\n";

        // Domain
        $output .= "🌐 DOMAIN\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= "{$data['domain']}\n\n";

        // Validation
        $output .= $this->getValidationMessages($data);

        return $output;
    }

    /**
     * Format LinkedIn Preview
     */
    protected function formatLinkedInPreview(array $data): string
    {
        $output = "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= "💼 LINKEDIN PREVIEW\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // Image
        $imageStatus = $data['image_status'] === 'valid' ? '✓' : '✗';
        $output .= "{$imageStatus} FEATURED IMAGE\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        if ($data['image']) {
            $output .= "Image URL: {$data['image']}\n";
            $output .= "Recommended: 1200x627px\n";
        } else {
            $output .= "⚠ No image set - LinkedIn will use a default placeholder\n";
        }
        $output .= "\n";

        // Title
        $titleStatus = $this->getStatusIcon($data['title_status']);
        $output .= "{$titleStatus} TITLE ({$data['title_length']}/{$data['title_limit']} chars)\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= $this->truncateForDisplay($data['title'], $data['title_limit']) . "\n\n";

        // Domain
        $output .= "🌐 DOMAIN\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= "{$data['domain']}\n\n";

        // Description
        $descStatus = $this->getStatusIcon($data['description_status']);
        $output .= "{$descStatus} DESCRIPTION ({$data['description_length']}/{$data['description_limit']} chars)\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= $this->truncateForDisplay($data['description'] ?? 'No description set', $data['description_limit']) . "\n\n";

        // Validation
        $output .= $this->getValidationMessages($data);

        return $output;
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

        if (empty($messages)) {
            return '';
        }

        $output = "VALIDATION\n";
        $output .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $output .= implode("\n", $messages) . "\n";

        return $output;
    }
}

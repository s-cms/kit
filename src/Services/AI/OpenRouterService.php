<?php

namespace SmartCms\Kit\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use MoeMizrak\LaravelOpenrouter\DTO\ChatData;
use MoeMizrak\LaravelOpenrouter\DTO\MessageData;
use MoeMizrak\LaravelOpenrouter\Facades\LaravelOpenRouter;
use MoeMizrak\LaravelOpenrouter\Types\RoleType;
use SmartCms\Kit\Models\Page;

/**
 * OpenRouter AI Service
 *
 * Provides AI-powered content generation using OpenRouter API
 * Supports multiple AI models (Claude, GPT, Gemini, Llama, etc.)
 *
 * Features:
 * - Generate SEO meta descriptions
 * - Generate keywords from content
 * - Generate summaries
 * - Translate content to multiple languages
 * - Configurable model selection per task type
 */
class OpenRouterService
{
    public string $model;

    public string $max_tokens;

    public function __construct()
    {
        $this->model = 'moonshotai/kimi-k2:free'; // config('openrouter.models.generation');
        $this->max_tokens = config('openrouter.max_tokens', 10000);
    }

    protected function getChatData(string $prompt): ChatData
    {
        $messageData = new MessageData(
            content: $prompt,
            role: RoleType::USER,
        );

        return new ChatData(messages: [$messageData], model: $this->model, max_tokens: $this->max_tokens);
    }

    protected function getResponse(ChatData $chatData): mixed
    {
        $chatResponse = LaravelOpenRouter::chatRequest($chatData);
        $response = $chatResponse->toArray();

        return $response['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Generate SEO meta description from page title and content
     */
    public function generateMetaDescription(string $title, ?string $content = null): string
    {
        $cacheKey = 'openrouter:meta:' . md5($title . $content);

        return Cache::remember($cacheKey, now()->addDay(), function () use ($title, $content) {
            $prompt = "Write a compelling SEO meta description (maximum 155 characters) for a webpage with the following title: \"{$title}\".";

            if ($content) {
                $contentPreview = Str::limit(strip_tags($content), 500);
                $prompt .= "\n\nContent preview: {$contentPreview}";
            }

            $prompt .= "\n\nRequirements:\n- Maximum 155 characters\n- Include relevant keywords\n- Make it engaging and click-worthy\n- Don't include quotes or special characters\n\nGenerate only the description text, nothing else:";
            $chatData = $this->getChatData($prompt);
            $text = $this->getResponse($chatData);

            return Str::limit($text, 155);
        });
    }

    /**
     * Generate SEO keywords from title and content
     */
    public function generateKeywords(string $title, ?string $content = null): string
    {
        $cacheKey = 'openrouter:keywords:' . md5($title . $content);

        return Cache::remember($cacheKey, now()->addDay(), function () use ($title, $content) {
            $prompt = "Extract the most relevant SEO keywords for a webpage with the title: \"{$title}\".";

            if ($content) {
                $contentPreview = Str::limit(strip_tags($content), 500);
                $prompt .= "\n\nContent preview: {$contentPreview}";
            }

            $prompt .= "\n\nRequirements:\n- Generate 5-10 relevant keywords\n- Separate with commas\n- Focus on search-relevant terms\n- Don't include the word 'keywords' or explanations\n\nGenerate only the comma-separated keywords:";

            $chatData = $this->getChatData($prompt);

            return $this->getResponse($chatData);
        });
    }

    /**
     * Generate content summary
     */
    public function generateSummary(string $title, string $content): string
    {
        $cacheKey = 'openrouter:summary:' . md5($title . $content);

        return Cache::remember($cacheKey, now()->addDay(), function () use ($title, $content) {
            $contentPreview = Str::limit(strip_tags($content), 1000);

            $prompt = "Write a concise summary (2-3 sentences, maximum 200 characters) for the following content:\n\nTitle: {$title}\n\nContent: {$contentPreview}\n\nRequirements:\n- 2-3 sentences maximum\n- Maximum 200 characters\n- Capture the main points\n- Clear and engaging\n\nGenerate only the summary text:";

            $chatData = $this->getChatData($prompt);

            return Str::limit($this->getResponse($chatData), 200);
        });
    }

    public function generateHeading(string $title, ?string $content = null): string
    {
        $cacheKey = 'openrouter:heading:' . md5($title . $content);

        return Cache::remember($cacheKey, now()->addDay(), function () use ($title, $content) {
            $prompt = "Generate a heading for a webpage with the title: \"{$title}\". Use the content to understand the topic and generate a heading that is relevant to the content.";
            if ($content) {
                $contentPreview = Str::limit(strip_tags($content), 500);
                $prompt .= "\n\nContent preview: {$contentPreview}";
            }
            $prompt .= "\n\nRequirements:\n- Maximum 200 characters\n- Include relevant keywords\n- Make it engaging and click-worthy\n- Don't include quotes or special characters\n\nGenerate only the heading text, nothing else:";
            $chatData = $this->getChatData($prompt);

            return $this->getResponse($chatData);
        });
    }

    /**
     * Generate all SEO fields at once
     */
    public function generateSeoFields(string $title, ?string $content = null): array
    {
        return [
            'description' => $this->generateMetaDescription($title, $content),
            'heading' => $this->generateHeading($title, $content),
            // 'keywords' => $this->generateKeywords($title, $content),
            'summary' => $content ? $this->generateSummary($title, $content) : null,
        ];
    }

    /**
     * Translate content to a specific language
     */
    public function translate(string $text, string $targetLanguage, string $sourceLanguage = 'en'): string
    {
        $cacheKey = 'openrouter:translate:' . md5($text . $targetLanguage);

        return Cache::remember($cacheKey, now()->addWeek(), function () use ($text, $targetLanguage, $sourceLanguage) {
            $languageNames = [
                'en' => 'English',
                'uk' => 'Ukrainian',
                'ru' => 'Russian',
                'es' => 'Spanish',
                'fr' => 'French',
                'de' => 'German',
                'it' => 'Italian',
                'pl' => 'Polish',
                'pt' => 'Portuguese',
                'ja' => 'Japanese',
                'zh' => 'Chinese',
                'ar' => 'Arabic',
            ];

            $sourceLang = $languageNames[$sourceLanguage] ?? $sourceLanguage;
            $targetLang = $languageNames[$targetLanguage] ?? $targetLanguage;

            $prompt = "Translate the following text from {$sourceLang} to {$targetLang}.\n\nText to translate:\n{$text}\n\nRequirements:\n- Maintain the original meaning and tone\n- Keep any HTML tags intact if present\n- Natural, fluent translation\n- Don't add explanations or notes\n\nTranslated text:";

            $chatData = $this->getChatData($prompt);

            return $this->getResponse($chatData);
        });
    }

    /**
     * Translate page content to all configured languages
     */
    public function translatePage(Page $page, array $fieldsToTranslate = ['name', 'title', 'heading', 'summary', 'content', 'description', 'keywords']): array
    {
        $mainLang = main_lang();
        $translations = [];

        $languages = app('lang')->adminLanguages()->where('slug', '!=', $mainLang);

        foreach ($languages as $language) {
            $translations[$language->slug] = [];

            foreach ($fieldsToTranslate as $field) {
                // Skip if target language already has content
                if ($page->hasTranslation($field, $language->slug)) {
                    $existingValue = $page->getTranslation($field, $language->slug);
                    if (! empty($existingValue)) {
                        continue;
                    }
                }

                // Get source text
                $sourceText = $page->getTranslation($field, $mainLang);
                if (empty($sourceText)) {
                    continue;
                }

                // Translate
                try {
                    $translations[$language->slug][$field] = $this->translate(
                        text: $sourceText,
                        targetLanguage: $language->slug,
                        sourceLanguage: $mainLang
                    );
                } catch (\Exception $e) {
                    logger()->error('Translation failed', [
                        'field' => $field,
                        'language' => $language->slug,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $translations;
    }

    /**
     * Check if OpenRouter API is configured
     */
    public function isConfigured(): bool
    {
        return ! empty(config('openrouter.api_key'));
    }

    /**
     * Get current model configuration
     */
    public function getModels(): array
    {
        return config('openrouter.models');
    }

    /**
     * Get API usage estimate (tokens)
     * Rough estimation: 1 token ≈ 4 characters
     */
    public function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }
}

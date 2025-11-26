<?php

namespace SmartCms\Kit\Services\SEO;

use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Services\AI\OpenRouterService;

/**
 * SEO Health Check Analyzer with AI-Powered Suggestions
 *
 * Analyzes page content and provides SEO health scores, recommendations,
 * and AI-generated improvement suggestions
 */
class SeoAnalyzer
{
    protected Page $page;

    protected array $results = [];

    protected int $totalScore = 0;

    protected int $maxScore = 0;

    protected ?OpenRouterService $ai = null;

    protected bool $withAiSuggestions = false;

    public function __construct(Page $page, bool $withAiSuggestions = false)
    {
        $this->page = $page;
        $this->withAiSuggestions = $withAiSuggestions;

        if ($withAiSuggestions) {
            try {
                $this->ai = app(OpenRouterService::class);
                if (! $this->ai->isConfigured()) {
                    $this->withAiSuggestions = false;
                }
            } catch (\Exception $e) {
                $this->withAiSuggestions = false;
            }
        }
    }

    /**
     * Run full SEO analysis
     */
    public function analyze(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        $this->results = [];
        $this->totalScore = 0;
        $this->maxScore = 0;

        // Run all checks
        $this->checkMetaTitle($locale);
        $this->checkMetaDescription($locale);
        $this->checkKeywords($locale);
        $this->checkHeadingStructure($locale);
        $this->checkContentLength($locale);
        $this->checkReadability($locale);
        $this->checkImages();

        $overallScore = $this->maxScore > 0 ? round(($this->totalScore / $this->maxScore) * 100) : 0;

        return [
            'overall_score' => $overallScore,
            'overall_status' => $this->getScoreStatus($overallScore),
            'checks' => $this->results,
            'total_score' => $this->totalScore,
            'max_score' => $this->maxScore,
        ];
    }

    /**
     * Generate AI suggestions for improvement
     */
    protected function generateAiSuggestions(string $checkName, string $problem, array $context): array
    {
        if (! $this->withAiSuggestions || ! $this->ai) {
            return [];
        }

        try {
            $suggestions = $this->ai->generateSeoImprovements($checkName, $problem, $context);

            return $suggestions;
        } catch (\Exception $e) {
            logger()->error('Failed to generate AI suggestions', [
                'check' => $checkName,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Check meta title
     */
    protected function checkMetaTitle(string $locale): void
    {
        $title = $this->page->getTranslation('title', $locale) ?? $this->page->getTranslation('name', $locale);
        $length = mb_strlen($title);

        $score = 0;
        $maxScore = 10;
        $status = 'error';
        $message = 'No title found';
        $suggestions = [];

        if ($length > 0) {
            if ($length >= 50 && $length <= 60) {
                $score = 10;
                $status = 'success';
                $message = "Perfect title length ({$length} characters)";
            } elseif ($length >= 40 && $length <= 70) {
                $score = 8;
                $status = 'warning';
                $message = "Good title length ({$length} characters), but could be optimized (50-60 is ideal)";

                if ($this->withAiSuggestions) {
                    $suggestions = $this->generateAiSuggestions('Meta Title', $message, [
                        'current_title' => $title,
                        'current_length' => $length,
                        'target_length' => '50-60',
                    ]);
                }
            } elseif ($length < 40) {
                $score = 5;
                $status = 'warning';
                $message = "Title too short ({$length} characters). Recommended: 50-60 characters";

                if ($this->withAiSuggestions) {
                    $suggestions = $this->generateAiSuggestions('Meta Title', $message, [
                        'current_title' => $title,
                        'current_length' => $length,
                        'target_length' => '50-60',
                        'page_content' => strip_tags($this->page->getTranslation('content', $locale) ?? ''),
                    ]);
                }
            } else {
                $score = 5;
                $status = 'warning';
                $message = "Title too long ({$length} characters). It may be truncated in search results. Recommended: 50-60 characters";

                if ($this->withAiSuggestions) {
                    $suggestions = $this->generateAiSuggestions('Meta Title', $message, [
                        'current_title' => $title,
                        'current_length' => $length,
                        'target_length' => '50-60',
                    ]);
                }
            }
        } else {
            if ($this->withAiSuggestions) {
                $suggestions = $this->generateAiSuggestions('Meta Title', $message, [
                    'page_name' => $this->page->getTranslation('name', $locale),
                    'page_content' => strip_tags($this->page->getTranslation('content', $locale) ?? ''),
                ]);
            }
        }

        $this->addResult('Meta Title', $score, $maxScore, $status, $message, $suggestions);
    }

    /**
     * Check meta description
     */
    protected function checkMetaDescription(string $locale): void
    {
        $description = $this->page->getTranslation('description', $locale);
        $length = mb_strlen($description ?? '');

        $score = 0;
        $maxScore = 10;
        $status = 'error';
        $message = 'No meta description found';
        $suggestions = [];

        if ($length > 0) {
            if ($length >= 140 && $length <= 160) {
                $score = 10;
                $status = 'success';
                $message = "Perfect description length ({$length} characters)";
            } elseif ($length >= 120 && $length <= 180) {
                $score = 8;
                $status = 'warning';
                $message = "Good description length ({$length} characters), but could be optimized (140-160 is ideal)";

                if ($this->withAiSuggestions) {
                    $suggestions = $this->generateAiSuggestions('Meta Description', $message, [
                        'current_description' => $description,
                        'current_length' => $length,
                        'target_length' => '140-160',
                        'page_title' => $this->page->getTranslation('title', $locale),
                    ]);
                }
            } elseif ($length < 120) {
                $score = 5;
                $status = 'warning';
                $message = "Description too short ({$length} characters). Recommended: 140-160 characters";

                if ($this->withAiSuggestions) {
                    $suggestions = $this->generateAiSuggestions('Meta Description', $message, [
                        'current_description' => $description,
                        'current_length' => $length,
                        'target_length' => '140-160',
                        'page_title' => $this->page->getTranslation('title', $locale),
                        'page_content' => strip_tags($this->page->getTranslation('content', $locale) ?? ''),
                    ]);
                }
            } else {
                $score = 5;
                $status = 'warning';
                $message = "Description too long ({$length} characters). It will be truncated. Recommended: 140-160 characters";

                if ($this->withAiSuggestions) {
                    $suggestions = $this->generateAiSuggestions('Meta Description', $message, [
                        'current_description' => $description,
                        'current_length' => $length,
                        'target_length' => '140-160',
                    ]);
                }
            }
        } else {
            if ($this->withAiSuggestions) {
                $suggestions = $this->generateAiSuggestions('Meta Description', $message, [
                    'page_title' => $this->page->getTranslation('title', $locale),
                    'page_content' => strip_tags($this->page->getTranslation('content', $locale) ?? ''),
                ]);
            }
        }

        $this->addResult('Meta Description', $score, $maxScore, $status, $message, $suggestions);
    }

    /**
     * Check keywords
     */
    protected function checkKeywords(string $locale): void
    {
        $keywords = $this->page->getTranslation('keywords', $locale);

        $score = 0;
        $maxScore = 5;
        $status = 'error';
        $message = 'No keywords found';
        $suggestions = [];

        if (! empty($keywords)) {
            $keywordArray = array_filter(array_map('trim', explode(',', $keywords)));
            $count = count($keywordArray);

            if ($count >= 5 && $count <= 10) {
                $score = 5;
                $status = 'success';
                $message = "Perfect keyword count ({$count} keywords)";
            } elseif ($count >= 3 && $count <= 15) {
                $score = 4;
                $status = 'warning';
                $message = "Good keyword count ({$count} keywords), but 5-10 is ideal";

                if ($this->withAiSuggestions) {
                    $suggestions = $this->generateAiSuggestions('Keywords', $message, [
                        'current_keywords' => $keywords,
                        'current_count' => $count,
                        'target_count' => '5-10',
                        'page_content' => strip_tags($this->page->getTranslation('content', $locale) ?? ''),
                    ]);
                }
            } elseif ($count > 0) {
                $score = 2;
                $status = 'warning';
                $message = "Too few or too many keywords ({$count}). Recommended: 5-10 keywords";

                if ($this->withAiSuggestions) {
                    $suggestions = $this->generateAiSuggestions('Keywords', $message, [
                        'current_keywords' => $keywords,
                        'current_count' => $count,
                        'target_count' => '5-10',
                        'page_content' => strip_tags($this->page->getTranslation('content', $locale) ?? ''),
                    ]);
                }
            }
        } else {
            if ($this->withAiSuggestions) {
                $suggestions = $this->generateAiSuggestions('Keywords', $message, [
                    'page_title' => $this->page->getTranslation('title', $locale),
                    'page_content' => strip_tags($this->page->getTranslation('content', $locale) ?? ''),
                ]);
            }
        }

        $this->addResult('Keywords', $score, $maxScore, $status, $message, $suggestions);
    }

    /**
     * Check heading structure (H1, H2, H3)
     */
    protected function checkHeadingStructure(string $locale): void
    {
        $content = $this->page->getTranslation('content', $locale) ?? '';

        $h1Count = substr_count($content, '<h1');
        $h2Count = substr_count($content, '<h2');
        $h3Count = substr_count($content, '<h3');

        $score = 0;
        $maxScore = 10;
        $status = 'error';
        $message = 'No headings found';
        $suggestions = [];

        if ($h1Count === 1 && $h2Count > 0) {
            $score = 10;
            $status = 'success';
            $message = "Perfect heading structure (1 H1, {$h2Count} H2, {$h3Count} H3)";
        } elseif ($h1Count === 0) {
            $score = 3;
            $status = 'warning';
            $message = 'Missing H1 heading. Every page should have exactly one H1';

            if ($this->withAiSuggestions) {
                $suggestions = $this->generateAiSuggestions('Heading Structure', $message, [
                    'page_title' => $this->page->getTranslation('title', $locale),
                    'page_content' => strip_tags($content),
                ]);
            }
        } elseif ($h1Count > 1) {
            $score = 5;
            $status = 'warning';
            $message = "Multiple H1 headings found ({$h1Count}). Use only one H1 per page";

            if ($this->withAiSuggestions) {
                $suggestions = $this->generateAiSuggestions('Heading Structure', $message, [
                    'current_h1_count' => $h1Count,
                    'page_title' => $this->page->getTranslation('title', $locale),
                ]);
            }
        } elseif ($h2Count === 0) {
            $score = 7;
            $status = 'warning';
            $message = 'H1 exists but no H2 subheadings. Consider adding subheadings for better structure';

            if ($this->withAiSuggestions) {
                $suggestions = $this->generateAiSuggestions('Heading Structure', $message, [
                    'page_content' => strip_tags($content),
                ]);
            }
        }

        $this->addResult('Heading Structure', $score, $maxScore, $status, $message, $suggestions);
    }

    /**
     * Check content length
     */
    protected function checkContentLength(string $locale): void
    {
        $content = strip_tags($this->page->getTranslation('content', $locale) ?? '');
        $wordCount = str_word_count($content);

        $score = 0;
        $maxScore = 10;
        $status = 'error';
        $message = 'No content found';
        $suggestions = [];

        if ($wordCount > 0) {
            if ($wordCount >= 300 && $wordCount <= 2000) {
                $score = 10;
                $status = 'success';
                $message = "Good content length ({$wordCount} words)";
            } elseif ($wordCount >= 150 && $wordCount < 300) {
                $score = 7;
                $status = 'warning';
                $message = "Content is a bit short ({$wordCount} words). 300+ words recommended for SEO";

                if ($this->withAiSuggestions) {
                    $suggestions = $this->generateAiSuggestions('Content Length', $message, [
                        'current_word_count' => $wordCount,
                        'target_word_count' => '300+',
                        'page_title' => $this->page->getTranslation('title', $locale),
                        'current_content' => $content,
                    ]);
                }
            } elseif ($wordCount > 2000) {
                $score = 9;
                $status = 'success';
                $message = "Long-form content ({$wordCount} words) - excellent for SEO!";
            } else {
                $score = 4;
                $status = 'warning';
                $message = "Content too short ({$wordCount} words). Minimum 150 words recommended";

                if ($this->withAiSuggestions) {
                    $suggestions = $this->generateAiSuggestions('Content Length', $message, [
                        'current_word_count' => $wordCount,
                        'target_word_count' => '300+',
                        'page_title' => $this->page->getTranslation('title', $locale),
                        'current_content' => $content,
                    ]);
                }
            }
        }

        $this->addResult('Content Length', $score, $maxScore, $status, $message, $suggestions);
    }

    /**
     * Check readability
     */
    protected function checkReadability(string $locale): void
    {
        $content = strip_tags($this->page->getTranslation('content', $locale) ?? '');

        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);
        $wordCount = str_word_count($content);

        $score = 0;
        $maxScore = 5;
        $status = 'success';
        $message = 'Content is readable';
        $suggestions = [];

        if ($wordCount > 0 && $sentenceCount > 0) {
            $averageWordsPerSentence = $wordCount / $sentenceCount;

            if ($averageWordsPerSentence <= 20) {
                $score = 5;
                $status = 'success';
                $message = sprintf('Good readability (avg %.1f words/sentence)', $averageWordsPerSentence);
            } elseif ($averageWordsPerSentence <= 25) {
                $score = 4;
                $status = 'warning';
                $message = sprintf('Readability could be improved (avg %.1f words/sentence). Try shorter sentences', $averageWordsPerSentence);

                if ($this->withAiSuggestions) {
                    $suggestions = $this->generateAiSuggestions('Readability', $message, [
                        'avg_words_per_sentence' => $averageWordsPerSentence,
                        'target' => '20 words or less per sentence',
                    ]);
                }
            } else {
                $score = 3;
                $status = 'warning';
                $message = sprintf('Poor readability (avg %.1f words/sentence). Consider breaking up long sentences', $averageWordsPerSentence);

                if ($this->withAiSuggestions) {
                    $suggestions = $this->generateAiSuggestions('Readability', $message, [
                        'avg_words_per_sentence' => $averageWordsPerSentence,
                        'target' => '20 words or less per sentence',
                    ]);
                }
            }
        } else {
            $score = 0;
            $status = 'error';
            $message = 'No content to analyze';
        }

        $this->addResult('Readability', $score, $maxScore, $status, $message, $suggestions);
    }

    /**
     * Check images have alt text
     */
    protected function checkImages(): void
    {
        $score = 5;
        $maxScore = 5;
        $status = 'success';
        $message = 'No images or all images have alt text';
        $suggestions = [];

        if ($this->page->image) {
            $hasAlt = ! empty($this->page->image['alt'] ?? '');
            if (! $hasAlt) {
                $score = 0;
                $status = 'warning';
                $message = 'Featured image is missing alt text';

                if ($this->withAiSuggestions) {
                    $suggestions = $this->generateAiSuggestions('Image Alt Text', $message, [
                        'page_title' => $this->page->getTranslation('title', main_lang()),
                        'page_content' => strip_tags($this->page->getTranslation('content', main_lang()) ?? ''),
                        'image_url' => $this->page->image['source'] ?? '',
                    ]);
                }
            }
        }

        $this->addResult('Image Alt Text', $score, $maxScore, $status, $message, $suggestions);
    }

    /**
     * Add a result to the analysis
     */
    protected function addResult(string $name, int $score, int $maxScore, string $status, string $message, array $suggestions = []): void
    {
        $this->results[] = [
            'name' => $name,
            'score' => $score,
            'max_score' => $maxScore,
            'status' => $status,
            'message' => $message,
            'percentage' => $maxScore > 0 ? round(($score / $maxScore) * 100) : 0,
            'suggestions' => $suggestions,
        ];

        $this->totalScore += $score;
        $this->maxScore += $maxScore;
    }

    /**
     * Get status based on score
     */
    protected function getScoreStatus(int $score): string
    {
        if ($score >= 90) {
            return 'excellent';
        } elseif ($score >= 70) {
            return 'good';
        } elseif ($score >= 50) {
            return 'needs_improvement';
        }

        return 'poor';
    }

    /**
     * Format analysis results as readable text
     */
    public function formatAsText(array $analysis): string
    {
        $output = "# SEO Health Check Report\n\n";
        $output .= "**Overall Score:** {$analysis['overall_score']}% - " . ucfirst(str_replace('_', ' ', $analysis['overall_status'])) . "\n\n";
        $output .= "---\n\n";

        foreach ($analysis['checks'] as $check) {
            $emoji = match ($check['status']) {
                'success' => '✅',
                'warning' => '⚠️',
                'error' => '❌',
                default => '�'
            };

            $output .= "## {$emoji} {$check['name']}\n";
            $output .= "**Score:** {$check['score']}/{$check['max_score']} ({$check['percentage']}%)\n";
            $output .= '**Status:** ' . ucfirst($check['status']) . "\n";
            $output .= "**Message:** {$check['message']}\n";

            if (! empty($check['suggestions'])) {
                $output .= "\n**AI Suggestions:**\n";
                foreach ($check['suggestions'] as $i => $suggestion) {
                    $output .= ($i + 1) . ". {$suggestion}\n";
                }
            }

            $output .= "\n---\n\n";
        }

        return $output;
    }
}

<?php

namespace SmartCms\Kit\Services\SEO;

use SmartCms\Kit\Models\Page;

/**
 * SEO Health Check Analyzer
 *
 * Analyzes page content and provides SEO health scores and recommendations
 */
class SeoAnalyzer
{
    protected Page $page;

    protected array $results = [];

    protected int $totalScore = 0;

    protected int $maxScore = 0;

    public function __construct(Page $page)
    {
        $this->page = $page;
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

        if ($length > 0) {
            if ($length >= 50 && $length <= 60) {
                $score = 10;
                $status = 'success';
                $message = "Perfect title length ({$length} characters)";
            } elseif ($length >= 40 && $length <= 70) {
                $score = 8;
                $status = 'warning';
                $message = "Good title length ({$length} characters), but could be optimized (50-60 is ideal)";
            } elseif ($length < 40) {
                $score = 5;
                $status = 'warning';
                $message = "Title too short ({$length} characters). Recommended: 50-60 characters";
            } else {
                $score = 5;
                $status = 'warning';
                $message = "Title too long ({$length} characters). It may be truncated in search results. Recommended: 50-60 characters";
            }
        }

        $this->addResult('Meta Title', $score, $maxScore, $status, $message);
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

        if ($length > 0) {
            if ($length >= 140 && $length <= 160) {
                $score = 10;
                $status = 'success';
                $message = "Perfect description length ({$length} characters)";
            } elseif ($length >= 120 && $length <= 180) {
                $score = 8;
                $status = 'warning';
                $message = "Good description length ({$length} characters), but could be optimized (140-160 is ideal)";
            } elseif ($length < 120) {
                $score = 5;
                $status = 'warning';
                $message = "Description too short ({$length} characters). Recommended: 140-160 characters";
            } else {
                $score = 5;
                $status = 'warning';
                $message = "Description too long ({$length} characters). It will be truncated. Recommended: 140-160 characters";
            }
        }

        $this->addResult('Meta Description', $score, $maxScore, $status, $message);
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
            } elseif ($count > 0) {
                $score = 2;
                $status = 'warning';
                $message = "Too few or too many keywords ({$count}). Recommended: 5-10 keywords";
            }
        }

        $this->addResult('Keywords', $score, $maxScore, $status, $message);
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

        if ($h1Count === 1 && $h2Count > 0) {
            $score = 10;
            $status = 'success';
            $message = "Perfect heading structure (1 H1, {$h2Count} H2, {$h3Count} H3)";
        } elseif ($h1Count === 0) {
            $score = 3;
            $status = 'warning';
            $message = 'Missing H1 heading. Every page should have exactly one H1';
        } elseif ($h1Count > 1) {
            $score = 5;
            $status = 'warning';
            $message = "Multiple H1 headings found ({$h1Count}). Use only one H1 per page";
        } elseif ($h2Count === 0) {
            $score = 7;
            $status = 'warning';
            $message = 'H1 exists but no H2 subheadings. Consider adding subheadings for better structure';
        }

        $this->addResult('Heading Structure', $score, $maxScore, $status, $message);
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

        if ($wordCount > 0) {
            if ($wordCount >= 300 && $wordCount <= 2000) {
                $score = 10;
                $status = 'success';
                $message = "Good content length ({$wordCount} words)";
            } elseif ($wordCount >= 150 && $wordCount < 300) {
                $score = 7;
                $status = 'warning';
                $message = "Content is a bit short ({$wordCount} words). 300+ words recommended for SEO";
            } elseif ($wordCount > 2000) {
                $score = 9;
                $status = 'success';
                $message = "Long-form content ({$wordCount} words) - excellent for SEO!";
            } else {
                $score = 4;
                $status = 'warning';
                $message = "Content too short ({$wordCount} words). Minimum 150 words recommended";
            }
        }

        $this->addResult('Content Length', $score, $maxScore, $status, $message);
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
            } else {
                $score = 3;
                $status = 'warning';
                $message = sprintf('Poor readability (avg %.1f words/sentence). Consider breaking up long sentences', $averageWordsPerSentence);
            }
        } else {
            $score = 0;
            $status = 'error';
            $message = 'No content to analyze';
        }

        $this->addResult('Readability', $score, $maxScore, $status, $message);
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

        if ($this->page->image) {
            $hasAlt = ! empty($this->page->image['alt'] ?? '');
            if (! $hasAlt) {
                $score = 0;
                $status = 'warning';
                $message = 'Featured image is missing alt text';
            }
        }

        $this->addResult('Image Alt Text', $score, $maxScore, $status, $message);
    }

    /**
     * Add a result to the analysis
     */
    protected function addResult(string $name, int $score, int $maxScore, string $status, string $message): void
    {
        $this->results[] = [
            'name' => $name,
            'score' => $score,
            'max_score' => $maxScore,
            'status' => $status,
            'message' => $message,
            'percentage' => $maxScore > 0 ? round(($score / $maxScore) * 100) : 0,
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
}

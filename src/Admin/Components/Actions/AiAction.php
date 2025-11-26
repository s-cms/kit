<?php

namespace SmartCms\Kit\Admin\Components\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use SmartCms\Kit\Services\AI\OpenRouterService;

/**
 * AI Action - Base action class for AI-powered features
 *
 * Provides predefined styling and visibility based on OpenRouterService configuration
 */
class AiAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon(Heroicon::Sparkles)
            ->color('success')
            ->iconButton()
            ->visible(fn () => self::isAiConfigured());
    }

    /**
     * Check if OpenRouterService is configured
     */
    protected static function isAiConfigured(): bool
    {
        try {
            $service = app(OpenRouterService::class);

            return $service->isConfigured();
        } catch (\Exception $e) {
            return false;
        }
    }
}

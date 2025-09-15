<?php

namespace SmartCms\Kit\Admin\Clusters\System\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use SmartCms\Kit\Admin\Clusters\System\SystemCluster;
use SmartCms\Kit\Contracts\UpdateCheckerInterface;
use SmartCms\Kit\Contracts\UpdateServiceInterface;

class UpdatePage extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::ArrowPath;

    protected string $view = 'filament-panels::pages.page';

    protected static ?int $navigationSort = 2;

    public static function getCluster(): ?string
    {
        return SystemCluster::class;
    }

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.updates');
    }

    public function getTitle(): string | Htmlable
    {
        return __('kit::admin.system_updates');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('kit.updates.enabled', true);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('kit::admin.current_version'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('current_version')
                                ->label(__('kit::admin.installed_version'))
                                ->state(fn () => $this->getCurrentVersion())
                                ->size(TextSize::Large)
                                ->weight('bold'),

                            TextEntry::make('last_checked')
                                ->label(__('kit::admin.last_checked'))
                                ->state(fn () => $this->getLastChecked() ?? __('kit::admin.never'))
                                ->visible(fn () => $this->getLastChecked() !== null),
                        ]),
                ]),

            Section::make($this->hasUpdatesAvailable() ? __('kit::admin.update_available') : __('kit::admin.up_to_date'))
                ->icon($this->hasUpdatesAvailable() ? Heroicon::ExclamationTriangle : Heroicon::CheckCircle)
                ->iconColor($this->hasUpdatesAvailable() ? 'warning' : 'success')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('latest_version')
                                ->label(__('kit::admin.latest_version'))
                                ->state(fn () => $this->getLatestVersion() ?? __('kit::admin.unknown'))
                                ->size(TextSize::Large)
                                ->weight('bold')
                                ->color($this->hasUpdatesAvailable() ? 'primary' : 'success')
                                ->visible(fn () => $this->hasUpdatesAvailable()),

                            TextEntry::make('release_date')
                                ->label(__('kit::admin.release_date'))
                                ->state(function () {
                                    $releaseInfo = $this->getReleaseInfo();
                                    if ($releaseInfo && isset($releaseInfo['published_at'])) {
                                        return \Carbon\Carbon::parse($releaseInfo['published_at'])->format('M j, Y');
                                    }

                                    return null;
                                })
                                ->visible(fn () => $this->hasUpdatesAvailable() && $this->getReleaseInfo() && isset($this->getReleaseInfo()['published_at'])),
                        ])
                        ->visible(fn () => $this->hasUpdatesAvailable()),

                    TextEntry::make('up_to_date_message')
                        ->label('')
                        ->state(__('kit::admin.system_up_to_date_message'))
                        ->visible(fn () => ! $this->hasUpdatesAvailable()),

                    TextEntry::make('release_notes')
                        ->label(__('kit::admin.release_notes'))
                        ->state(function () {
                            $releaseInfo = $this->getReleaseInfo();
                            if ($releaseInfo && isset($releaseInfo['body'])) {
                                return \Illuminate\Support\Str::limit($releaseInfo['body'], 500);
                            }

                            return null;
                        })
                        ->markdown()
                        ->visible(fn () => $this->hasUpdatesAvailable() && $this->getReleaseInfo() && isset($this->getReleaseInfo()['body'])),
                ]),

            Section::make(__('kit::admin.update_settings'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('automatic_checking')
                                ->label(__('kit::admin.automatic_checking'))
                                ->state(config('kit.updates.enabled') ? __('kit::admin.enabled') : __('kit::admin.disabled'))
                                ->icon(config('kit.updates.enabled') ? Heroicon::CheckCircle : Heroicon::XCircle)
                                ->color(config('kit.updates.enabled') ? 'success' : 'gray'),

                            TextEntry::make('check_frequency')
                                ->label(__('kit::admin.check_frequency'))
                                ->state(ucfirst(config('kit.updates.check_frequency', 'login'))),
                        ]),

                    TextEntry::make('settings_note')
                        ->label('')
                        ->state(__('kit::admin.update_settings_note'))
                        ->color('gray')
                        ->size(TextSize::Small),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('updateAssets')
                ->label(__('kit::admin.update_assets'))
                ->icon(Heroicon::CommandLine)
                ->action('updateAssets')
                ->color('gray')
                ->disabled(fn () => ! $this->canUpdateAssets())
                ->tooltip(function () {
                    if (! $this->canUpdateAssets()) {
                        $issues = $this->getAssetValidationIssues();

                        return __('kit::admin.asset_update_disabled') . ': ' . implode(', ', $issues);
                    }

                    return null;
                })
                ->requiresConfirmation()
                ->modalHeading(__('kit::admin.confirm_asset_update'))
                ->modalDescription(__('kit::admin.confirm_asset_update_description'))
                ->modalSubmitActionLabel(__('kit::admin.update_assets')),

            Action::make('checkUpdates')
                ->label(__('kit::admin.check_for_updates'))
                ->icon(Heroicon::ArrowPath)
                ->action('checkForUpdates')
                ->color('gray'),

            Action::make('updateNow')
                ->label(__('kit::admin.update_now'))
                ->icon(Heroicon::CloudArrowDown)
                ->action('updateNow')
                ->color('primary')
                ->visible(fn () => $this->hasUpdatesAvailable())
                ->requiresConfirmation()
                ->modalHeading(__('kit::admin.confirm_update'))
                ->modalDescription(__('kit::admin.confirm_update_description'))
                ->modalSubmitActionLabel(__('kit::admin.update_now')),
        ];
    }

    public function checkForUpdates(): void
    {
        try {
            $updateChecker = app(UpdateCheckerInterface::class);
            $updateChecker->checkOnLogin();

            Notification::make()
                ->title(__('kit::admin.update_check_completed'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('kit::admin.update_check_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function updateNow(): void
    {
        try {
            $updateExecutor = new \SmartCms\Kit\Services\UpdateExecutor;

            // Validate environment before starting
            $validation = $updateExecutor->validateUpdateEnvironment();
            if (! $validation['valid']) {
                Notification::make()
                    ->title(__('kit::admin.update_validation_failed'))
                    ->body(implode("\n", $validation['issues']))
                    ->danger()
                    ->send();

                return;
            }
            // Execute the update
            $result = $updateExecutor->executeUpdate();

            if ($result['success']) {
                Notification::make()
                    ->title(__('kit::admin.update_completed'))
                    ->body(__('kit::admin.update_completed_message'))
                    ->success()
                    ->send();

                // Clear update notifications since we've updated
                $updateChecker = app(\SmartCms\Kit\Contracts\UpdateCheckerInterface::class);
                $updateChecker->clearUpdateNotifications();
            } else {
                $notification = Notification::make()
                    ->title(__('kit::admin.update_failed'))
                    ->body($result['message'])
                    ->danger();

                // Add troubleshooting information if available
                if (! empty($result['troubleshooting'])) {
                    $troubleshootingText = implode("\n• ", $result['troubleshooting']);
                    $notification->body($result['message'] . "\n\nTroubleshooting:\n• " . $troubleshootingText);
                }

                $notification->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('kit::admin.update_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function updateAssets(): void
    {
        try {
            $assetUpdater = new \SmartCms\Kit\Services\AssetUpdater;

            // Validate environment before starting
            $validation = $assetUpdater->validateAssetEnvironment();
            if (! $validation['valid']) {
                Notification::make()
                    ->title(__('kit::admin.asset_update_validation_failed'))
                    ->body(implode("\n", $validation['issues']))
                    ->danger()
                    ->send();

                return;
            }

            // Execute the asset update
            $result = $assetUpdater->updateAssets();

            if ($result['success']) {
                Notification::make()
                    ->title(__('kit::admin.asset_update_completed'))
                    ->body(__('kit::admin.asset_update_completed_message'))
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title(__('kit::admin.asset_update_failed'))
                    ->body($result['message'])
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('kit::admin.asset_update_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getCurrentVersion(): string
    {
        $updateService = app(UpdateServiceInterface::class);

        return $updateService->getCurrentVersion();
    }

    public function getUpdateDetails(): ?array
    {
        $updateChecker = app(UpdateCheckerInterface::class);

        return $updateChecker->getUpdateNotifications();
    }

    public function hasUpdatesAvailable(): bool
    {
        $details = $this->getUpdateDetails();

        return $details && ($details['has_updates'] ?? false);
    }

    public function getLatestVersion(): ?string
    {
        $details = $this->getUpdateDetails();

        return $details['latest_version'] ?? null;
    }

    public function getReleaseInfo(): ?array
    {
        $details = $this->getUpdateDetails();

        return $details['release_info'] ?? null;
    }

    public function getLastChecked(): ?string
    {
        $details = $this->getUpdateDetails();
        if (! $details || ! isset($details['checked_at'])) {
            return null;
        }

        return \Carbon\Carbon::parse($details['checked_at'])->diffForHumans();
    }

    public function canUpdateAssets(): bool
    {
        return Cache::remember('can_update_assets', 300, function () {
            $assetUpdater = new \SmartCms\Kit\Services\AssetUpdater;
            $validation = $assetUpdater->validateAssetEnvironment();

            return $validation['valid'];
        });
        // $assetUpdater = new \SmartCms\Kit\Services\AssetUpdater();
        // $validation = $assetUpdater->validateAssetEnvironment();
        // return $validation['valid'];
    }

    public function getAssetValidationIssues(): array
    {
        $assetUpdater = new \SmartCms\Kit\Services\AssetUpdater;
        $validation = $assetUpdater->validateAssetEnvironment();

        return $validation['issues'];
    }
}

<?php

namespace SmartCms\Kit;

use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentAsset;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use SmartCms\Forms\FormsPlugin;
use SmartCms\Kit\Actions\Admin\GetInboxButton;
use SmartCms\Kit\Actions\Admin\GetVersionHtml;
use SmartCms\Kit\Actions\Admin\GetViewButton;
use SmartCms\Kit\Admin\Clusters\Design\DesignCluster;
use SmartCms\Kit\Admin\Pages\Dashboard;
use SmartCms\Kit\Admin\Pages\Login;
use SmartCms\Kit\Admin\Pages\Profile;
use SmartCms\Kit\Admin\Pages\Settings;
use SmartCms\Kit\Admin\Pages\TranslatesPage;
use SmartCms\Kit\Admin\Resources\Admins\AdminResource;
use SmartCms\Kit\Admin\Resources\Pages\PageResource;
use SmartCms\Kit\Admin\Widgets\ContactFormStatsWidget;
use SmartCms\Kit\Admin\Widgets\HealthCheck;
use SmartCms\Kit\Admin\Widgets\InfoWidget;
use SmartCms\Kit\Admin\Widgets\VersionsWidget;
use SmartCms\Kit\Http\Middlewares\NoIndex;
use SmartCms\Kit\Http\Middlewares\SetAdminLocale;
use SmartCms\Kit\Models\Admin;
use SmartCms\Kit\Models\Page;
use SmartCms\Menu\MenuPlugin;
use SmartCms\TemplateBuilder\TemplateBuilderPlugin;
use SmartCms\Theme\Theme;

class KitPlugin implements Plugin
{
    public function getId(): string
    {
        return 'kit';
    }

    public function register(Panel $panel): void
    {
        $resources = [];
        if (! $panel->getModelResource(Page::class)) {
            $resources[] = PageResource::class;
        }
        if (! $panel->getModelResource(Admin::class)) {
            $resources[] = AdminResource::class;
        }
        FilamentAsset::register([
            Css::make('custom', public_path('kit/css/custom.css')),
        ]);
        $panel->plugins([
            new Theme,
            TemplateBuilderPlugin::make(null, DesignCluster::class),
            MenuPlugin::make(null, DesignCluster::class),
            FormsPlugin::make(),
        ])
            ->discoverClusters(in: __DIR__ . '/Admin/Clusters', for: 'SmartCms\Kit\Admin\Clusters')
            ->profile(Profile::class, isSimple: false)
            ->login(Login::class)
            ->authGuard('admin')
            ->topNavigation()
            ->brandName(app('s')->get('company_name', 'SmartCms')) // TODO: add company name to config
            ->spa()
            ->unsavedChangesAlerts()
            ->databaseNotifications()
            ->resources($resources)
            ->widgets([
                HealthCheck::class,
                InfoWidget::class,
                ContactFormStatsWidget::class,
                VersionsWidget::class,
            ])
            ->middleware([
                NoIndex::class,
                SetAdminLocale::class,
            ])
            ->renderHook(PanelsRenderHook::PAGE_END, GetVersionHtml::run())
            ->renderHook(PanelsRenderHook::HEAD_START, fn (): string => '<meta name="robots" content="noindex, nofollow" />')
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER, GetInboxButton::run())
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER, GetViewButton::run())
            ->breadcrumbs(false)
            ->maxContentWidth(Width::Full)
            ->pages([
                Dashboard::class,
                Settings::class,
                TranslatesPage::class,
            ]);

        Table::configureUsing(function (Table $table) {
            $table->defaultSort('updated_at', 'desc');
        });
    }

    public function boot(Panel $panel): void
    {
        Action::configureUsing(function (Action $action) {
            $action->size('sm');
            if ($action->getIcon()) {
                $action->iconPosition(IconPosition::After)->iconSize('xs');
            }
        });
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}

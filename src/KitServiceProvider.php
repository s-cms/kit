<?php

namespace SmartCms\Kit;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route as FacadesRoute;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use SmartCms\Forms\Models\ContactForm;
use SmartCms\Kit\Actions\Support\BindConfig;
use SmartCms\Kit\Actions\Support\RegisterVariableTypes;
use SmartCms\Kit\Commands\ActivatePages;
use SmartCms\Kit\Commands\CreateLanguages;
use SmartCms\Kit\Commands\MakeAdmin;
use SmartCms\Kit\Commands\MakeHomePage;
use SmartCms\Kit\Commands\StartMcp;
use SmartCms\Kit\Commands\SyncBlockSchemas;
use SmartCms\Kit\Commands\Update;
use SmartCms\Kit\Components\Footer;
use SmartCms\Kit\Components\Gtm;
use SmartCms\Kit\Components\Header;
use SmartCms\Kit\Components\Heading;
use SmartCms\Kit\Components\Icon;
use SmartCms\Kit\Components\Image;
use SmartCms\Kit\Components\Layout;
use SmartCms\Kit\Components\Link;
use SmartCms\Kit\Components\PageComponent;
use SmartCms\Kit\Components\Theme;
use SmartCms\Kit\Console\Commands\MakeAugmentationCommand;
use SmartCms\Kit\Http\Middlewares\HtmlMinifier;
use SmartCms\Kit\Http\Middlewares\Maintenance;
use SmartCms\Kit\Http\Middlewares\UserIdentifierMiddleware;
use SmartCms\Kit\MenuTypes\DivisionCategoryMenyType;
use SmartCms\Kit\MenuTypes\DivisionMenuType;
use SmartCms\Kit\MenuTypes\PageMenuType;
use SmartCms\Kit\Observers\ContactFormObserver;
use SmartCms\Kit\Observers\MediaObserver;
use SmartCms\Kit\Support\AssetManager;
use SmartCms\Kit\Support\MicrodataManager;
use SmartCms\Kit\Support\Seo;
use SmartCms\Kit\Testing\TestsKit;
use SmartCms\Lang\Middlewares\Lang;
use SmartCms\Menu\MenuRegistry;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class KitServiceProvider extends PackageServiceProvider
{
    public static string $name = 'kit';

    public static string $viewNamespace = 'kit';

    public static ?array $viewShare = null;

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands([
                MakeAdmin::class,
                Update::class,
                MakeHomePage::class,
                CreateLanguages::class,
                ActivatePages::class,
                MakeAugmentationCommand::class,
                SyncBlockSchemas::class,
                StartMcp::class,
            ])
            ->hasConfigFile()
            ->hasMigrations([
                'create_admins_table',
                'create_pages_table',
                'alter_admins_table',
                'create_blocks_table',
                'create_blockables_table',
                'add_type_and_metadata_to_pages_table',
                'create_block_templates_table',
            ])
            ->hasTranslations()
            ->hasRoute('static')
            ->hasViews('kit')
            ->hasViewComponents('kit', Layout::class, Footer::class, Theme::class, Gtm::class, Header::class, PageComponent::class, Heading::class, Image::class, Link::class, Icon::class)
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publish('images')
                    ->startWith(function (InstallCommand $command): void {
                        $command->callSilently('vendor:publish', [
                            '--tag' => 'settings-migrations',
                        ]);
                        $command->callSilently('vendor:publish', [
                            '--tag' => 'lang-migrations',
                        ]);
                        $command->callSilently('vendor:publish', [
                            '--tag' => 'seo-migrations',
                        ]);
                        $command->callSilently('vendor:publish', [
                            '--tag' => 'menu-migrations',
                        ]);
                        $command->callSilently('vendor:publish', [
                            '--tag' => 'forms-migrations',
                        ]);
                        $command->callSilently('vendor:publish', [
                            '--tag' => 'template-builder-migrations',
                        ]);
                        $command->callSilently('vendor:publish', [
                            '--tag' => 'model-translate-migrations',
                        ]);
                        $command->callSilently('vendor:publish', [
                            '--tag' => 'notifications-migrations',
                        ]);
                        $command->callSilently('vendor:publish', [
                            '--tag' => 'medialibrary-migrations',
                        ]);
                        $command->callSilently('vendor:publish', [
                            '--tag' => 'laravel-errors',
                        ]);
                        $command->callSilently('notifications:table');
                    })
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('smart-cms/kit')
                    ->endWith(function (InstallCommand $command): void {
                        $command->call('vendor:publish', ['--tag' => 'kit-images']);
                        $command->call('vendor:publish', ['--tag' => 'kit-css']);
                        $command->call('kit:create-languages');
                        $command->call('make:home-page');
                        if (File::exists(public_path('robots.txt'))) {
                            File::move(public_path('robots.txt'), public_path('robots.txt.backup'));
                        }
                        if (File::exists(public_path('sitemap.xml'))) {
                            File::move(public_path('sitemap.xml'), public_path('sitemap.xml.backup'));
                        }
                        $this->createDirectory(resource_path('views/sections'));
                        $this->createDirectory(resource_path('views/layouts'));
                        $this->createDirectory(resource_path('views/layouts/pages'));
                        $this->createDirectory(resource_path('views/layouts/divisions'));
                        $command->call('make:layout', ['name' => 'header']);
                        $command->call('make:layout', ['name' => 'footer']);
                        $command->call('make:layout', ['name' => 'pages.home']);
                        $command->call('filament:install');
                        $command->call('filament:assets');
                        if (File::exists(resource_path('views/welcome.blade.php'))) {
                            File::delete(resource_path('views/welcome.blade.php'));
                        }
                        $this->updateGitignore();
                    });
            });
    }

    public function packageRegistered(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('maintenance', Maintenance::class);
        $router->aliasMiddleware('html.minifier', HtmlMinifier::class);
        $router->aliasMiddleware('uuid', UserIdentifierMiddleware::class);
        if (! Route::hasMacro('multilingual')) {
            Route::macro('multilingual', function () {
                /** @var \Illuminate\Routing\Route $this */
                $uri = $this->uri();
                $cleanUri = ltrim($uri, '/');
                $actions = array_filter($this->getAction(), fn ($key): bool => $key != 'as', ARRAY_FILTER_USE_KEY);
                FacadesRoute::addRoute(
                    $this->methods(),
                    '{lang}/' . $cleanUri,
                    $actions
                )->where('lang', '[a-z]{2}')->name($this->getName() . '.lang')->middleware('lang');

                return $this->middleware('lang');
            });
        }

        // Register non-dependent singletons early
        $this->app->singleton('seo', fn (): \SmartCms\Kit\Support\Seo => new Seo);
        $this->app->singleton(MicrodataManager::class, fn (): \SmartCms\Kit\Support\MicrodataManager => new MicrodataManager);
        $this->app->alias(MicrodataManager::class, 'microdata');
        $this->app->singleton(AssetManager::class, fn (): \SmartCms\Kit\Support\AssetManager => new AssetManager);
        $this->app->alias(AssetManager::class, 'assets');
    }

    public function packageBooted(): void
    {
        Testable::mixin(new TestsKit);
        $this->configureDefaults();
        RegisterVariableTypes::run();

        // Register dependent services that rely on other services/config
        $this->app->singleton(\SmartCms\Kit\Contracts\UpdateServiceInterface::class, fn (): \SmartCms\Kit\Services\UpdateService => new \SmartCms\Kit\Services\UpdateService);
        $this->app->singleton(\SmartCms\Kit\Contracts\UpdateCheckerInterface::class, fn (): \SmartCms\Kit\Services\UpdateChecker => new \SmartCms\Kit\Services\UpdateChecker(
            $this->app->make(\SmartCms\Kit\Contracts\UpdateServiceInterface::class)
        ));
        $this->app->singleton(\SmartCms\Kit\Services\UpdateExecutor::class, fn (): \SmartCms\Kit\Services\UpdateExecutor => new \SmartCms\Kit\Services\UpdateExecutor);
        $this->app->singleton(\SmartCms\Kit\Services\AssetUpdater::class, fn (): \SmartCms\Kit\Services\AssetUpdater => new \SmartCms\Kit\Services\AssetUpdater);

        app(MenuRegistry::class)->register(PageMenuType::class);
        app(MenuRegistry::class)->register(DivisionMenuType::class);
        app(MenuRegistry::class)->register(DivisionCategoryMenyType::class);
        ContactForm::observe(ContactFormObserver::class);
        Media::observe(MediaObserver::class);

        // Routes and config must be loaded after all other services are booted
        $this->app->booted(function (): void {
            BindConfig::run();
            $this->mergeAuthConfigFrom();
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/images/' => storage_path('app/public'),
            ], 'kit-images');
            $this->publishes([
                __DIR__ . '/../resources/dist/custom.css' => public_path('kit/css/custom.css'),
            ], 'kit-css');
        }
        if (Schema::hasTable(config('settings.database_table_name', 'settings'))) {
            View::composer('*', function ($view): void {
                $vars = $this->getSharedVariables();
                foreach ($vars as $key => $value) {
                    $view->with($key, $value);
                }
            });
        }
    }

    protected function configureDefaults(): void
    {
        Vite::useAggressivePrefetching();
        if (app()->isProduction()) {
            URL::forceHttps();
        }
        Model::automaticallyEagerLoadRelationships();
        Date::use(CarbonImmutable::class);
        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );
        Model::shouldBeStrict();
        Model::unguard();
        Livewire::setUpdateRoute(function ($handle) {
            $isAdmin = request()->is('admin/*');
            if ($isAdmin) {
                return FacadesRoute::post('/livewire/update', $handle);
            }

            return FacadesRoute::post('/livewire/update', $handle)
                ->middleware(['web', Lang::class]);
        });
    }

    protected function mergeAuthConfigFrom(): void
    {
        $custom = [
            'guards' => [
                'admin' => [
                    'driver' => 'session',
                    'provider' => 'admin',
                ],
            ],
            'providers' => [
                'admin' => [
                    'driver' => 'eloquent',
                    'model' => config('kit.auth_model', \SmartCms\Kit\Models\Admin::class),
                ],
            ],
        ];

        foreach ($custom as $key => $values) {
            $existing = config("auth.$key", []);
            config(["auth.$key" => array_merge($existing, $values)]);
        }
    }

    public function createDirectory($path): void
    {
        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    protected function updateGitignore()
    {
        $gitignorePath = base_path('.gitignore');
        $filesToIgnore = [
            '# Smart CMS',
            '*.zip',
            '*.tar.gz',
            'public/css/filament/*',
            'public/js/filament/*',
            'public/fonts/filament/*',
            '', // Empty line for separation
        ];

        if (file_exists($gitignorePath)) {
            $currentContent = file_get_contents($gitignorePath);

            // Check if our entries already exist to avoid duplicates
            $marker = '# Smart CMS';
            if (in_array(str_contains($currentContent, $marker), [0, false], true)) {
                $newContent = $currentContent . "\n" . implode("\n", $filesToIgnore);
                file_put_contents($gitignorePath, $newContent);
            }
        }
    }

    protected function getSharedVariables(): array
    {
        if (static::$viewShare !== null && static::$viewShare !== []) {
            return static::$viewShare;
        }
        $data = [
            'host' => [
                'title' => hostname(),
                'type' => 'link',
                'is_external' => false,
                'url' => host(),
            ],
            'hostname' => hostname(),
            'company_name' => company_name(),
            'logo' => logo(),
            'languages' => language_routes(),
        ];
        static::$viewShare = $data;

        return $data;
    }
}

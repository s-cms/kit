<?php

namespace SmartCms\Kit\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use SmartCms\Kit\KitServiceProvider;
use SmartCms\Lang\LangServiceProvider;
use SmartCms\Menu\MenuServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'SmartCms\\Kit\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            MenuServiceProvider::class,
            KitServiceProvider::class,
            LangServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        // Run the migrations for tests
        $adminsMigration = include __DIR__ . '/../database/migrations/create_admins_table.php.stub';
        $adminsMigration->up();

        $pagesMigration = include __DIR__ . '/../database/migrations/create_pages_table.php.stub';
        $pagesMigration->up();

        $blocksMigration = include __DIR__ . '/../database/migrations/create_blocks_table.php.stub';
        $blocksMigration->up();

        $blockablesMigration = include __DIR__ . '/../database/migrations/create_blockables_table.php.stub';
        $blockablesMigration->up();

        $blockTemplatesMigration = include __DIR__ . '/../database/migrations/create_block_templates_table.php.stub';
        $blockTemplatesMigration->up();

        $addTypeAndMetadataToPagesMigration = include __DIR__ . '/../database/migrations/add_type_and_metadata_to_pages_table.php.stub';
        $addTypeAndMetadataToPagesMigration->up();

        $menuMigration = include __DIR__ . '/../vendor/smart-cms/menu/database/migrations/create_menus_table.php.stub';
        $menuMigration->up();
        $langMigration = include __DIR__ . '/../vendor/smart-cms/lang/database/migrations/create_languages_table.php.stub';
        $langMigration->up();

        // Mock the 's' service that's used in KitPlugin
        $app->singleton('s', fn (): object => new class
        {
            public function get($key, $default = null)
            {
                return $default;
            }
        });

        // Mock the 'lang' service that's used in helpers
        $app->singleton('lang', fn () => new class
        {
            public function current()
            {
                return 'en';
            }

            public function default()
            {
                return (object) ['slug' => 'en', 'name' => 'English', 'default' => true, 'active' => true];
            }

            public function adminLanguages()
            {
                return collect([
                    (object) ['slug' => 'en', 'name' => 'English', 'default' => true, 'active' => true],
                ]);
            }
        });
    }
}

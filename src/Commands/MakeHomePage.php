<?php

namespace SmartCms\Kit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use SmartCms\Kit\Models\Page;
use SmartCms\Lang\Database\Factories\LanguageFactory;
use SmartCms\Lang\Models\Language;

class MakeHomePage extends Command
{
    protected $signature = 'make:home-page';

    protected $description = 'Make scms home page';

    public function handle(): void
    {
        if (Language::query()->count() === 0) {
            LanguageFactory::new()->create([
                'name' => 'English',
                'slug' => 'en',
                'locale' => 'en_US',
                'is_default' => true,
                'is_admin_active' => true,
                'is_frontend_active' => true,
            ]);
        }
        $slug = app('lang')->default()->slug ?? 'en';
        $page = Page::query()->updateOrCreate([
            'id' => 1,
        ], [
            'name' => [
                $slug => 'Home',
            ],
            'slug' => '',
            'status' => true,
            'is_system' => true,
        ]);
        DB::table($page->getTable())->where('id', $page->id)->update([
            'slug' => '',
        ]);
        $this->info('Home page created successfully');
    }
}

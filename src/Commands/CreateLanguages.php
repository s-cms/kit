<?php

namespace SmartCms\Kit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use SmartCms\Lang\Database\Factories\LanguageFactory;
use SmartCms\Lang\Models\Language;

class CreateLanguages extends Command
{
    protected $signature = 'kit:create-languages';

    public function handle(): void
    {
        if (! Schema::hasTable('languages')) {
            $this->error('Languages table does not exist');

            return;
        }
        Language::query()->delete();
        $languages = [
            [
                'name' => 'English',
                'slug' => 'en',
                'locale' => 'en_US',
                'is_default' => true,
                'is_admin_active' => true,
                'is_frontend_active' => true,
            ],
            [
                'name' => 'Русский',
                'slug' => 'ru',
                'locale' => 'ru_RU',
                'is_default' => false,
                'is_admin_active' => false,
                'is_frontend_active' => false,
            ],
            [
                'name' => 'Українська',
                'slug' => 'uk',
                'locale' => 'uk_UA',
                'is_default' => false,
                'is_admin_active' => false,
                'is_frontend_active' => false,
            ],
            [
                'name' => 'Polski',
                'slug' => 'pl',
                'locale' => 'pl_PL',
                'is_default' => false,
                'is_admin_active' => false,
                'is_frontend_active' => false,
            ],
            [
                'name' => 'Deutsch',
                'slug' => 'de',
                'locale' => 'de_DE',
                'is_default' => false,
                'is_admin_active' => false,
                'is_frontend_active' => false,
            ],
            [
                'name' => 'Français',
                'slug' => 'fr',
                'locale' => 'fr_FR',
                'is_default' => false,
                'is_admin_active' => false,
                'is_frontend_active' => false,
            ],
            [
                'name' => 'Español',
                'slug' => 'es',
                'locale' => 'es_ES',
                'is_default' => false,
                'is_admin_active' => false,
                'is_frontend_active' => false,
            ],
            [
                'name' => 'Italiano',
                'slug' => 'it',
                'locale' => 'it_IT',
                'is_default' => false,
                'is_admin_active' => false,
                'is_frontend_active' => false,
            ],
            [
                'name' => 'Português',
                'slug' => 'pt',
                'locale' => 'pt_PT',
                'is_default' => false,
                'is_admin_active' => false,
                'is_frontend_active' => false,
            ],
            [
                'name' => '中文',
                'slug' => 'zh',
                'locale' => 'zh_CN',
                'is_default' => false,
                'is_admin_active' => false,
                'is_frontend_active' => false,
            ],
            [
                'name' => '日本語',
                'slug' => 'ja',
                'locale' => 'ja_JP',
                'is_default' => false,
                'is_admin_active' => false,
                'is_frontend_active' => false,
            ],
            [
                'name' => '한국어',
                'slug' => 'ko',
                'locale' => 'ko_KR',
                'is_default' => false,
                'is_admin_active' => false,
                'is_frontend_active' => false,
            ],
        ];
        foreach ($languages as $language) {
            LanguageFactory::new()->create($language);
        }
    }
}

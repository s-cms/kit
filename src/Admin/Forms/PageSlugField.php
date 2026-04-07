<?php

namespace SmartCms\Kit\Admin\Forms;

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use SmartCms\Support\Admin\Components\Forms\SlugField;

class PageSlugField
{
    public static function make(?string $name = 'slug'): TextInput
    {
        return SlugField::make($name)->required(false)
            ->readOnly()
            ->unique(config('kit.pages_table_name'), 'slug', modifyRuleUsing: function ($rule, $get, $set) {
                if (blank($get('slug'))) {
                    $set('slug', Str::slug($get('name')[main_lang()]));
                }

                return $rule;
            });
    }
}

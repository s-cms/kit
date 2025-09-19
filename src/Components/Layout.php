<?php

namespace SmartCms\Kit\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Layout extends Component
{
    public array $scripts;

    public array $meta_tags;

    public string $favicon;

    public string $og_type;

    /**
     * @var array{prefix: mixed, suffix: mixed}
     */
    public $titleMod;

    /**
     * @var array{prefix: mixed, suffix: mixed}
     */
    public $descriptionMod;

    public string $stylePath;

    public function __construct()
    {
        $stylePath = file_exists(resource_path('scss/app.scss')) ? 'resources/scss/app.scss' : 'resources/css/app.css';
        $this->stylePath = $stylePath;

        $this->scripts = app('s')->get('custom_scripts', []);
        $meta_tags = app('s')->get('custom_meta', []);
        $this->meta_tags = $meta_tags;
        $fav = app('s')->get('branding.favicon', '/favicon.ico');
        if (str_starts_with((string) $fav, '/')) {
            $fav = substr((string) $fav, 1);
        }
        $this->favicon = asset('/storage/' . $fav);
        $this->og_type = app('s')->get('og_type', 'website') ?? 'website';
        $this->titleMod = [
            'prefix' => app('s')->get('title.prefix', ''),
            'suffix' => app('s')->get('title.suffix', ''),
        ];
        $this->descriptionMod = [
            'prefix' => app('s')->get('description.prefix', ''),
            'suffix' => app('s')->get('description.suffix', ''),
        ];
    }

    public function render(): View | Closure | string
    {
        return view('kit::layout');
    }
}

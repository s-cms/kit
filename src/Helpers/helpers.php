<?php

use SmartCms\Kit\Models\Page;

if (! function_exists('validateImage')) {
    function validateImage(mixed $image = null): string | array
    {
        $source = $image;
        if (is_array($image)) {
            $source = $image['source'] ?? null;
        }
        if (! $source) {
            if (is_string($image)) {
                return no_image()['source'];
            }

            return no_image();
        }
        if (! str_contains((string) $source, 'storage')) {
            if (! str_starts_with((string) $source, '/')) {
                $source = '/' . $source;
            }
            $source = asset('storage' . $source);
        }
        if (is_array($image)) {
            $image['source'] = $source;

            return $image;
        }

        return $source;
    }
}

if (! function_exists('no_image')) {
    function no_image(): array
    {
        return once(function () {
            $no_image = app('s')->get('no_image', []);
            if (! isset($no_image['source'])) {
                $no_image['source'] = '/no-image.webp';
            }
            $no_image['source'] = validateImage($no_image['source']);
            $no_image['width'] = 100;
            $no_image['height'] = 100;
            $no_image['alt'] = 'No image';

            return $no_image;
        });
    }
}
if (! function_exists('logo')) {
    function logo(): array
    {
        $logo = app('s')->get('branding.logo', no_image());

        return validateImage($logo);
    }
}

if (! function_exists('company_name')) {
    function company_name(): string
    {
        return app('s')->get('company_name', config('app.name'));
    }
}
if (! function_exists('host')) {
    function host(): string
    {
        return url('/');
    }
}

if (! function_exists('hostname')) {
    function hostname(): string
    {
        return once(fn() => Page::query()->first()->name ?? __('Hostname'));
    }
}

if (! function_exists('language_routes')) {
    function language_routes(): array
    {
        $routes = [];
        $currentLocale = app()->getLocale();
        $currentPath = url()->current();

        foreach (app('lang')->frontLanguages() as $lang) {
            $path = $currentPath;
            if ($lang->slug === $currentLocale) {
                $routes[] = [
                    'name' => $lang->name,
                    'code' => $lang->slug,
                    'route' => $path,
                ];

                continue;
            }
            if ($lang->slug === main_lang()) {
                $path = preg_replace('#/' . $currentLocale . '(/|$)#', '/', $currentPath);
                $path = rtrim((string) $path, '/');
            } else {
                $parts = parse_url($currentPath);
                $base = $parts['scheme'] . '://' . $parts['host'];
                if (isset($parts['port'])) {
                    $base .= ':' . $parts['port'];
                }
                $purePath = preg_replace('#^' . $currentLocale . '/?#', '', ltrim($parts['path'] ?? '', '/'));
                $newPath = $lang->slug . '/' . $purePath;

                $path = $base . '/' . $newPath;
            }

            $routes[] = [
                'name' => $lang->name,
                'code' => $lang->slug,
                'route' => $path,
            ];
        }

        return $routes;
    }
}

<?php

use SmartCms\Kit\Http\Resources\MediaResource;
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
            if (! isset($no_image['source']) || empty($no_image['source'])) {
                $no_image['source'] = no_image_placeholder();
            } else {
                $no_image['source'] = validateImage($no_image['source']);
            }
            $no_image['width'] = $no_image['width'] ?? 100;
            $no_image['height'] = $no_image['height'] ?? 100;
            $no_image['alt'] = $no_image['alt'] ?? 'No image';

            return $no_image;
        });
    }
}

if (! function_exists('no_image_placeholder')) {
    function no_image_placeholder(): string
    {
        return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200' viewBox='0 0 200 200'%3E%3Crect width='200' height='200' fill='%23f3f4f6'/%3E%3Cpath d='M80 90a10 10 0 1 1 0-20 10 10 0 0 1 0 20zm60 40H60l30-40 15 20 10-13z' fill='%239ca3af'/%3E%3C/svg%3E";
    }
}

if (! function_exists('logo')) {
    function logo(): array
    {
        $logo = app('s')->get('branding.logo', no_image());
        if (is_array($logo)) {
            return validateImage($logo);
        }

        return MediaResource::make($logo)->toArray(request());
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
        return once(fn () => Page::query()->first()->name ?? __('Hostname'));
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

if (! function_exists('format_bytes')) {
    function format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

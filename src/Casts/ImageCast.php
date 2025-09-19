<?php

namespace SmartCms\Kit\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ImageCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        $values = is_array($value) ? $value : (filled($value) ? json_decode((string) $value, true) : null);
        if ($values === null) {
            return no_image();
        }

        return validateImage($values);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        return $value;
    }
}

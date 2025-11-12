<?php

namespace SmartCms\Kit\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use SmartCms\Kit\Support\Contracts\PageStatus;

class PageStatusCast implements CastsAttributes
{
    public function get(Model $model, string $key, $value, array $attributes): string
    {
        if (! $value) {
            return PageStatus::Draft->value;
        }

        return $value;
    }

    public function set(Model $model, string $key, $value, array $attributes): string
    {
        if (isset($attributes['id']) && $attributes['id'] == 1) {
            return PageStatus::Published->value;
        }

        return $value instanceof PageStatus ? $value->value : $value;
    }
}

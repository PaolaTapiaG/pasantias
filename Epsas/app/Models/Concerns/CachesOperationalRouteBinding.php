<?php

namespace App\Models\Concerns;

use App\Support\OperationalCache;

trait CachesOperationalRouteBinding
{
    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();
        $cacheName = 'route-binding:'.str_replace('\\', '.', static::class).":{$field}:{$value}";

        return OperationalCache::remember(
            $cacheName,
            fn () => $this->where($field, $value)->first(),
            now()->addHours(6)
        );
    }
}

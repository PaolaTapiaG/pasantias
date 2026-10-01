<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CachedEloquentUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials): ?UserContract
    {
        $credentials = array_filter(
            $credentials,
            fn ($key) => ! Str::contains($key, 'password'),
            ARRAY_FILTER_USE_KEY
        );

        if ($credentials === [] || Arr::first($credentials) instanceof \Closure) {
            return null;
        }

        return Cache::remember(
            self::credentialCacheKey($credentials),
            now()->addMinutes((int) config('auth.user_cache_minutes', 1440)),
            function () use ($credentials) {
                $query = $this->newModelQuery()->with('persona');

                foreach ($credentials as $key => $value) {
                    if ($value instanceof \Closure) {
                        $value($query);
                    } else {
                        $query->where($key, $value);
                    }
                }

                return $query->first();
            }
        );
    }

    public function retrieveById($identifier): ?UserContract
    {
        if (empty($identifier)) {
            return null;
        }

        return Cache::remember(
            $this->cacheKey($identifier),
            now()->addMinutes((int) config('auth.user_cache_minutes', 10)),
            function () use ($identifier) {
                $model = $this->createModel();

                return $model->newQuery()
                    ->with('persona')
                    ->where($model->getAuthIdentifierName(), $identifier)
                    ->first();
            }
        );
    }

    public function retrieveByToken($identifier, $token): ?UserContract
    {
        $user = $this->retrieveById($identifier);

        if (! $user) {
            return null;
        }

        $rememberToken = $user->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token) ? $user : null;
    }

    public function updateRememberToken(UserContract $user, $token): void
    {
        parent::updateRememberToken($user, $token);

        Cache::forget($this->cacheKey($user->getAuthIdentifier()));
    }

    private function cacheKey(mixed $identifier): string
    {
        return 'auth:user:'.str_replace('\\', '.', $this->model).':'.$identifier;
    }

    public static function credentialCacheKey(array $credentials): string
    {
        ksort($credentials);

        return 'auth:credentials:'.sha1(json_encode($credentials));
    }
}

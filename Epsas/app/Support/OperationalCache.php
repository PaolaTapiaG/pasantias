<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

final class OperationalCache
{
    private const VERSION_KEY = 'tecnico:operational-cache-version';

    private const PREFIX = 'tecnico:operational';

    private const DOMAIN_PREFIX = 'crm:cache';

    public static function remember(string $name, Closure $callback, mixed $ttl = null): mixed
    {
        return Cache::remember(
            self::key($name),
            $ttl ?? now()->addMinutes(30),
            $callback
        );
    }

    public static function key(string $name): string
    {
        return self::PREFIX.':v'.self::version().':'.$name;
    }

    public static function forget(string $name): void
    {
        Cache::forget(self::key($name));
    }

    public static function rememberDomain(string $domain, string $name, Closure $callback, mixed $ttl = null): mixed
    {
        return Cache::remember(self::domainKey($domain, $name), $ttl ?? now()->addMinutes(10), $callback);
    }

    public static function forgetDomain(string $domain, ?string $name = null): void
    {
        $name === null
            ? self::bumpDomain($domain)
            : Cache::forget(self::domainKey($domain, $name));
    }

    private static function domainKey(string $domain, string $name): string
    {
        return self::DOMAIN_PREFIX.':'.$domain.':v'.self::domainVersion($domain).':'.$name;
    }

    private static function domainVersion(string $domain): int
    {
        $key = self::DOMAIN_PREFIX.':'.$domain.':version';
        Cache::add($key, 1, now()->addYears(2));

        return (int) Cache::get($key, 1);
    }

    public static function bumpDomain(string $domain): void
    {
        $key = self::DOMAIN_PREFIX.':'.$domain.':version';
        Cache::add($key, 1, now()->addYears(2));
        Cache::increment($key);
    }

    /**
     * Prefer targeted invalidation for common writes. A global bump is still
     * available for rare structural changes, but it makes every operational
     * screen cold on the next request.
     */
    public static function version(): int
    {
        Cache::add(self::VERSION_KEY, 1, now()->addYears(2));

        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function bump(): void
    {
        Cache::add(self::VERSION_KEY, 1, now()->addYears(2));
        Cache::increment(self::VERSION_KEY);
    }
}

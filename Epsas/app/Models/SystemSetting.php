<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use App\Support\OperationalCache;
use Illuminate\Support\Str;
use Throwable;

class SystemSetting extends Model
{
    private const SECRET_FIELDS = [
        'messaging' => ['sms_gateway_api_key', 'sms_gateway_password'],
        'mail' => ['password', 'api_key'],
    ];

    protected $table = 'system_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = 'system_setting:' . $key;

        try {
            $payload = OperationalCache::rememberDomain('settings', 'system-setting.'.$key, function () use ($key) {
                $setting = static::query()->where('key', $key)->first();

                return ['value' => $setting?->value];
            });
        } catch (QueryException $exception) {
            if (! static::causedByMissingSettingsTable($exception)) {
                throw $exception;
            }

            return static::decryptSecrets($key, $default);
        }

        $value = match (true) {
            is_array($payload) && array_key_exists('value', $payload) => $payload['value'],
            $payload instanceof self => $payload->value,
            default => $payload,
        };

        return static::decryptSecrets($key, $value ?? $default);
    }

    public static function putValue(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => static::encryptSecrets($key, $value)]
        );

        Cache::forget('system_setting:' . $key);
        OperationalCache::bumpDomain('settings');
    }

    private static function encryptSecrets(string $key, mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach (self::SECRET_FIELDS[$key] ?? [] as $field) {
            if (! filled($value[$field] ?? null) || Str::startsWith((string) $value[$field], 'encrypted:')) {
                continue;
            }

            $value[$field] = 'encrypted:'.Crypt::encryptString((string) $value[$field]);
        }

        return $value;
    }

    private static function decryptSecrets(string $key, mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach (self::SECRET_FIELDS[$key] ?? [] as $field) {
            if (! Str::startsWith((string) ($value[$field] ?? ''), 'encrypted:')) {
                continue;
            }

            try {
                $value[$field] = Crypt::decryptString(Str::after((string) $value[$field], 'encrypted:'));
            } catch (Throwable) {
                $value[$field] = null;
            }
        }

        return $value;
    }

    private static function causedByMissingSettingsTable(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'system_settings')
            && (str_contains($message, 'Undefined table')
                || str_contains($message, 'no such table')
                || str_contains($message, 'does not exist'));
    }
}

<?php

namespace App\Support;

use App\Models\Medidor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SequentialMedidorNumber
{
    public static function next(): string
    {
        $max = Cache::remember(
            'medidores:next-numero-serie',
            now()->addMinutes(10),
            fn () => self::maxNumericSuffix()
        );

        return self::format($max + 1);
    }

    public static function forgetCache(): void
    {
        Cache::forget('medidores:next-numero-serie');
    }

    public static function format(int $number): string
    {
        return 'MED-'.str_pad((string) max(1, $number), 5, '0', STR_PAD_LEFT);
    }

    private static function maxNumericSuffix(): int
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return (int) DB::table('medidores')
                ->where('numero_serie', 'like', 'MED-%')
                ->selectRaw("COALESCE(MAX(CAST(SUBSTRING(numero_serie FROM 'MED-([0-9]+)$') AS INTEGER)), 0) as max_num")
                ->value('max_num');
        }

        if ($driver === 'sqlite') {
            return (int) DB::table('medidores')
                ->where('numero_serie', 'like', 'MED-%')
                ->selectRaw('COALESCE(MAX(CAST(SUBSTR(numero_serie, 5) AS INTEGER)), 0) as max_num')
                ->value('max_num');
        }

        return Medidor::query()
            ->where('numero_serie', 'like', 'MED-%')
            ->pluck('numero_serie')
            ->map(fn ($value) => self::numericSuffix((string) $value))
            ->filter(fn (?int $value) => $value !== null)
            ->max() ?? 0;
    }

    private static function numericSuffix(string $value): ?int
    {
        if (preg_match('/MED-(\d+)$/i', trim($value), $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }
}

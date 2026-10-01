<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const SECRET_FIELDS = [
        'messaging' => ['sms_gateway_api_key', 'sms_gateway_password'],
        'mail' => ['password', 'api_key'],
    ];

    public function up(): void
    {
        foreach (self::SECRET_FIELDS as $key => $fields) {
            $raw = DB::table('system_settings')->where('key', $key)->value('value');
            $value = json_decode((string) $raw, true);

            if (! is_array($value)) {
                continue;
            }

            foreach ($fields as $field) {
                if (! filled($value[$field] ?? null) || Str::startsWith((string) $value[$field], 'encrypted:')) {
                    continue;
                }

                $value[$field] = 'encrypted:'.Crypt::encryptString((string) $value[$field]);
            }

            DB::table('system_settings')->where('key', $key)->update([
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Secrets remain encrypted intentionally.
    }
};

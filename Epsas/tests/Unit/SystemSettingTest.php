<?php

namespace Tests\Unit;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SystemSettingTest extends TestCase
{
    public function test_missing_settings_table_returns_the_requested_default(): void
    {
        Cache::flush();

        $this->assertSame(
            ['company_name' => 'EPSAS'],
            SystemSetting::getValue('general', ['company_name' => 'EPSAS'])
        );
    }
}

<?php

namespace Tests\Unit;

use App\Support\OperationalCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OperationalCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_domain_version_invalidation_recomputes_cached_data(): void
    {
        $calls = 0;
        $resolver = function () use (&$calls): string {
            $calls++;

            return 'value-'.$calls;
        };

        $this->assertSame('value-1', OperationalCache::rememberDomain('billing', 'summary', $resolver));
        $this->assertSame('value-1', OperationalCache::rememberDomain('billing', 'summary', $resolver));

        OperationalCache::bumpDomain('billing');

        $this->assertSame('value-2', OperationalCache::rememberDomain('billing', 'summary', $resolver));
        $this->assertSame(2, $calls);
    }

    public function test_domain_versions_are_isolated(): void
    {
        $billingCalls = 0;
        $operationsCalls = 0;

        $billing = function () use (&$billingCalls): string {
            return 'billing-'.(++$billingCalls);
        };
        $operations = function () use (&$operationsCalls): string {
            return 'operations-'.(++$operationsCalls);
        };

        $this->assertSame('billing-1', OperationalCache::rememberDomain('billing', 'summary', $billing));
        $this->assertSame('operations-1', OperationalCache::rememberDomain('operations', 'summary', $operations));

        OperationalCache::bumpDomain('billing');

        $this->assertSame('billing-2', OperationalCache::rememberDomain('billing', 'summary', $billing));
        $this->assertSame('operations-1', OperationalCache::rememberDomain('operations', 'summary', $operations));
    }
}

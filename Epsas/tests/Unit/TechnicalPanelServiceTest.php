<?php

namespace Tests\Unit;

use App\Services\TechnicalPanelService;
use ReflectionMethod;
use Tests\TestCase;

class TechnicalPanelServiceTest extends TestCase
{
    public function test_catalog_cache_key_is_stable_for_the_same_query(): void
    {
        $service = new TechnicalPanelService();
        $method = new ReflectionMethod(TechnicalPanelService::class, 'catalogCacheKey');
        $method->setAccessible(true);

        $keyA = $method->invoke($service, 'medidores', 'agua', 20);
        $keyB = $method->invoke($service, 'medidores', 'agua', 20);

        $this->assertSame($keyA, $keyB);
        $this->assertStringContainsString('tecnico:catalog:medidores', $keyA);
    }
}

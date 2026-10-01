<?php

namespace Tests\Unit;

use App\Services\WaterBillingService;
use PHPUnit\Framework\TestCase;

class WaterBillingServiceTest extends TestCase
{
    private WaterBillingService $billing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->billing = new class extends WaterBillingService
        {
            public function settings(): array
            {
                return [
                    'included_m3' => 10,
                    'fixed_charge' => 20,
                    'excess_rate' => 3,
                    'cutoff_threshold_m3' => 30,
                    'reconnection_fee' => 30,
                    'sewer_fixed_charge' => 5,
                ];
            }
        };
    }

    public function test_included_consumption_only_charges_fixed_services(): void
    {
        $result = $this->billing->breakdown(10);

        $this->assertEquals(0.0, $result['excess_m3']);
        $this->assertEquals(20.0, $result['water_charge']);
        $this->assertEquals(25.0, $result['total']);
    }

    public function test_excess_and_cutoff_penalty_are_calculated_once(): void
    {
        $result = $this->billing->breakdown(31);

        $this->assertEquals(21.0, $result['excess_m3']);
        $this->assertEquals(63.0, $result['excess_charge']);
        $this->assertEquals(30.0, $result['cutoff_penalty']);
        $this->assertEquals(118.0, $result['total']);
    }

    public function test_negative_consumption_never_reduces_the_bill(): void
    {
        $result = $this->billing->breakdown(-5);

        $this->assertEquals(0.0, $result['excess_m3']);
        $this->assertEquals(25.0, $result['total']);
    }

    public function test_tariff_overrides_are_used_for_the_calculation(): void
    {
        $result = $this->billing->breakdown(12, [
            'included_m3' => 5,
            'fixed_charge' => 8,
            'excess_rate' => 1.8,
        ]);

        $this->assertEquals(7.0, $result['excess_m3']);
        $this->assertEquals(12.6, $result['excess_charge']);
        $this->assertEquals(25.6, $result['total']);
    }

    public function test_reconnection_fee_is_included_in_breakdown_output(): void
    {
        $result = $this->billing->breakdown(31);

        $this->assertArrayHasKey('reconnection_fee', $result);
        $this->assertEquals(30.0, $result['reconnection_fee']);
        $this->assertEquals(30.0, $result['cutoff_penalty']);
    }
}

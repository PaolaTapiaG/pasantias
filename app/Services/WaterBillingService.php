<?php

namespace App\Services;

use App\Models\SystemSetting;

class WaterBillingService
{
    public function settings(): array
    {
        $general = SystemSetting::getValue('general', []);

        return [
            'included_m3' => (float) ($general['included_m3'] ?? 10),
            'fixed_charge' => (float) ($general['fixed_charge'] ?? 20),
            'excess_rate' => (float) ($general['excess_rate'] ?? 3),
            'cutoff_threshold_m3' => (float) ($general['cutoff_threshold_m3'] ?? 30),
            'reconnection_fee' => (float) ($general['reconnection_fee'] ?? 30),
            'sewer_fixed_charge' => (float) ($general['sewer_fixed_charge'] ?? 0),
        ];
    }

    public function breakdown(float $consumoM3): array
    {
        $settings = $this->settings();
        $includedM3 = $settings['included_m3'];
        $fixedCharge = $settings['fixed_charge'];
        $excessRate = $settings['excess_rate'];
        $threshold = $settings['cutoff_threshold_m3'];
        $reconnectionFee = $settings['reconnection_fee'];
        $sewerFixedCharge = $settings['sewer_fixed_charge'];

        $billableConsumption = max(0, $consumoM3);
        $excessM3 = max(0, $billableConsumption - $includedM3);
        $excessCharge = round($excessM3 * $excessRate, 2);
        $cutoffPenalty = $billableConsumption > $threshold ? $reconnectionFee : 0.0;
        $waterCharge = round($fixedCharge + $excessCharge, 2);
        $subtotal = round($waterCharge + $sewerFixedCharge, 2);
        $total = round($subtotal + $cutoffPenalty, 2);

        return [
            'included_m3' => $includedM3,
            'fixed_charge' => $fixedCharge,
            'excess_m3' => $excessM3,
            'excess_rate' => $excessRate,
            'excess_charge' => $excessCharge,
            'cutoff_threshold_m3' => $threshold,
            'cutoff_penalty' => $cutoffPenalty,
            'sewer_fixed_charge' => $sewerFixedCharge,
            'water_charge' => $waterCharge,
            'subtotal' => $subtotal,
            'total' => $total,
        ];
    }
}

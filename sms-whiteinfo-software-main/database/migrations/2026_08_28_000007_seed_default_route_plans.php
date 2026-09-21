<?php

use App\Models\Gateway;
use App\Models\RoutePlan;
use App\Models\RoutePlanGateway;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $gateways = Gateway::where('is_enabled', true)->orderBy('priority', 'asc')->get();

        if ($gateways->isEmpty()) {
            $gateways = Gateway::all();
        }

        // 1. Default OTP Route Plan
        $otpPlan = RoutePlan::firstOrCreate(
            ['code' => 'OTP_DEFAULT'],
            [
                'name' => 'Default OTP Priority Route',
                'traffic_type' => 'otp',
                'description' => 'High-priority route with multi-provider failover for OTP verification codes.',
                'is_default' => true,
                'is_enabled' => true,
                'failover_strategy' => 'priority_ordered',
            ]
        );

        // 2. Default Transactional Route Plan
        $transPlan = RoutePlan::firstOrCreate(
            ['code' => 'TRANS_DEFAULT'],
            [
                'name' => 'Default Transactional Route',
                'traffic_type' => 'transactional',
                'description' => 'Fast delivery route for transactional notifications and critical alerts.',
                'is_default' => true,
                'is_enabled' => true,
                'failover_strategy' => 'priority_ordered',
            ]
        );

        // 3. Default Promotional Route Plan
        $promoPlan = RoutePlan::firstOrCreate(
            ['code' => 'PROMO_DEFAULT'],
            [
                'name' => 'Default Promotional Route',
                'traffic_type' => 'promotional',
                'description' => 'Cost-optimized bulk route for marketing and promotional campaigns.',
                'is_default' => true,
                'is_enabled' => true,
                'failover_strategy' => 'priority_ordered',
            ]
        );

        // Assign gateways to plans
        foreach ([$otpPlan, $transPlan, $promoPlan] as $plan) {
            $priority = 1;
            foreach ($gateways as $gw) {
                RoutePlanGateway::firstOrCreate(
                    [
                        'route_plan_id' => $plan->id,
                        'gateway_id' => $gw->id,
                    ],
                    [
                        'priority' => $priority++,
                        'weight' => 100,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        RoutePlan::whereIn('code', ['OTP_DEFAULT', 'TRANS_DEFAULT', 'PROMO_DEFAULT'])->delete();
    }
};

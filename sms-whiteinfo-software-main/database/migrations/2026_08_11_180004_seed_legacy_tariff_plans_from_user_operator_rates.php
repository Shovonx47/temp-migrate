<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Legacy data migration: converts existing user_operator_rates rows into
 * auto-generated "Custom" tariff plans assigned to each customer.
 *
 * Strategy: Option A (zero-downtime, day-one safe).
 * - Each customer who has at least one user_operator_rates row gets one
 *   new tariff_plans row (type=custom, status=active) named "[Name] Legacy Plan".
 * - Their rate rows are copied into tariff_rates for that plan.
 * - A tariff_plan_assignments row is created linking customer → plan.
 * - Customers with NO user_operator_rates rows get no assignment; the
 *   OperatorRateResolver falls through to the operator system default.
 *
 * The user_operator_rates table is NOT dropped — it remains as a data archive
 * and a safety net until the migration is verified clean.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Load all users who have at least one operator rate override
        $usersWithRates = DB::table('user_operator_rates')
            ->select('user_id')
            ->distinct()
            ->get()
            ->pluck('user_id');

        $now = now();
        $today = today()->toDateString();

        foreach ($usersWithRates as $userId) {
            // Fetch user name for a readable plan label
            $user = DB::table('users')->where('id', $userId)->first();

            if (! $user) {
                continue;
            }

            $planName = trim($user->name).' Legacy Plan';

            // Create the tariff plan
            $planId = DB::table('tariff_plans')->insertGetId([
                'name' => $planName,
                'type' => 'custom',
                'status' => 'active',
                'currency' => 'BDT',
                'description' => 'Auto-generated from legacy per-operator rate overrides during tariff system migration.',
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Copy operator rate rows into tariff_rates
            $rates = DB::table('user_operator_rates')
                ->where('user_id', $userId)
                ->get();

            foreach ($rates as $rate) {
                DB::table('tariff_rates')->insertOrIgnore([
                    'tariff_plan_id' => $planId,
                    'operator_id' => $rate->operator_id,
                    'masking_price' => $rate->masking_price,
                    'non_masking_price' => $rate->non_masking_price,
                    'effective_from' => null,
                    'notes' => 'Migrated from user_operator_rates.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Assign the plan to the customer
            DB::table('tariff_plan_assignments')->insert([
                'user_id' => $userId,
                'tariff_plan_id' => $planId,
                'assigned_by' => null,
                'effective_from' => $today,
                'status' => 'active',
                'notes' => 'Auto-assigned during tariff system migration.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Remove only the auto-generated legacy plans and their assignments.
        // We identify them by the description marker used above.
        $legacyPlanIds = DB::table('tariff_plans')
            ->where('description', 'Auto-generated from legacy per-operator rate overrides during tariff system migration.')
            ->pluck('id');

        if ($legacyPlanIds->isNotEmpty()) {
            // Assignments and rates cascade-delete when the plan is deleted.
            DB::table('tariff_plans')->whereIn('id', $legacyPlanIds)->delete();
        }
    }
};

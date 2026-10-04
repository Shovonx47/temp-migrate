<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: safe to re-run if a previous attempt stopped half-way.
        if (! Schema::hasColumn('tariff_plans', 'slug')) {
            Schema::table('tariff_plans', function (Blueprint $table) {
                // Nullable on purpose: existing code paths that insert plans without a slug keep working.
                // The unique index still prevents duplicates (NULLs do not collide).
                $table->string('slug', 64)->nullable()->unique()->after('name');
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('tariff_plans', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }

    /**
     * Give every existing plan a unique slug. Inline on purpose (no models, no services)
     * so this migration keeps working no matter how the app code changes later.
     */
    private function backfill(): void
    {
        $taken = array_fill_keys(
            array_merge(
                DB::table('tariff_plans')->whereNotNull('slug')->pluck('slug')->all(),
                DB::table('signup_slug_aliases')->pluck('slug')->all(),
            ),
            true,
        );

        DB::table('tariff_plans')
            ->whereNull('slug')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function (object $plan) use (&$taken): void {
                $base = trim(substr(Str::slug((string) $plan->name), 0, 56), '-');
                $base = $base === '' ? 'plan' : $base;

                $slug = $base;
                for ($i = 2; isset($taken[$slug]); $i++) {
                    $slug = $base.'-'.$i;
                }

                $taken[$slug] = true;

                // DB facade on purpose: does not touch updated_at and skips model events.
                DB::table('tariff_plans')->where('id', $plan->id)->update(['slug' => $slug]);
            });
    }
};

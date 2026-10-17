<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signup_slug_aliases', function (Blueprint $table) {
            $table->id();
            // Same global namespace as tariff_plans.slug.
            $table->string('slug', 64)->unique();
            // NULL means the plan was deleted: the slug stays reserved and its link is dead.
            $table->foreignId('tariff_plan_id')
                ->nullable()
                ->constrained('tariff_plans')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signup_slug_aliases');
    }
};

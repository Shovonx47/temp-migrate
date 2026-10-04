<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->string('traffic_type', 30)->default('all'); // otp, transactional, promotional, all
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->string('failover_strategy', 30)->default('priority_ordered'); // priority_ordered, round_robin, lowest_cost
            $table->timestamps();
        });

        Schema::create('route_plan_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_plan_id')->constrained('route_plans')->cascadeOnDelete();
            $table->foreignId('gateway_id')->constrained('gateways')->cascadeOnDelete();
            $table->unsignedSmallInteger('priority')->default(1); // 1 = Primary, 2 = Secondary, 3 = Tertiary
            $table->unsignedSmallInteger('weight')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['route_plan_id', 'gateway_id']);
        });

        Schema::create('route_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('route_plan_id')->constrained('route_plans')->cascadeOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('operators')->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('senders')->nullOnDelete();
            $table->string('traffic_type', 30)->nullable(); // otp, transactional, promotional
            $table->unsignedSmallInteger('priority')->default(1);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_rules');
        Schema::dropIfExists('route_plan_gateways');
        Schema::dropIfExists('route_plans');
    }
};

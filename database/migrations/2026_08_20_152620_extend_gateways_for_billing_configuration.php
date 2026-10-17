<?php

use App\Enums\GatewayCostMode;
use App\Enums\GatewayDriver;
use App\Models\Gateway;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, bool> */
    private array $dlrSupportByDriver = [
        'revesms' => true,
        'qsms' => true,
        'anbernet' => true,
        'infozillion' => true,
        'log' => false,
    ];

    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->boolean('dlr_support')->default(false)->after('supports_bulk');
            $table->string('cost_mode')->default(GatewayCostMode::Manual->value)->after('dlr_support');
            $table->boolean('dipping_required')->default(false)->after('cost_mode');
            $table->boolean('has_third_party_dipping')->default(false)->after('dipping_required');
        });

        Gateway::query()->each(function (Gateway $gateway): void {
            $gateway->forceFill([
                'dlr_support' => $this->dlrSupportByDriver[$gateway->driver->value] ?? false,
                'dipping_required' => $gateway->driver === GatewayDriver::Infozillion,
            ])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn([
                'dlr_support',
                'cost_mode',
                'dipping_required',
                'has_third_party_dipping',
            ]);
        });
    }
};

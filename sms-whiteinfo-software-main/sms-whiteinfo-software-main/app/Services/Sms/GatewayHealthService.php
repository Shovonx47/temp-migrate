<?php

namespace App\Services\Sms;

use App\Contracts\SupportsDashboard;
use App\Models\Gateway;
use App\Support\Facades\Sms;
use Illuminate\Support\Facades\Http;
use Throwable;

class GatewayHealthService
{
    /**
     * Test connectivity and balance for a specific gateway.
     *
     * @return array{
     *     status: 'healthy'|'degraded'|'down',
     *     latency_ms: int,
     *     message: string,
     *     balance: ?string,
     *     details: array<string, mixed>
     * }
     */
    public function checkGateway(Gateway $gateway): array
    {
        $start = hrtime(true);
        $timeout = $gateway->timeout_seconds ?? 10;
        $baseUrl = $gateway->credentials['base_url'] ?? null;
        $driver = $gateway->driver?->value ?? 'log';

        if ($driver === 'log') {
            $latency = (int) ((hrtime(true) - $start) / 1_000_000);
            $result = [
                'status' => 'healthy',
                'latency_ms' => max(1, $latency),
                'message' => 'Log driver active and operational (local sink).',
                'balance' => 'Unlimited',
                'details' => ['driver' => 'log'],
            ];

            $this->persistHealth($gateway, $result);

            return $result;
        }

        if (blank($baseUrl)) {
            $latency = (int) ((hrtime(true) - $start) / 1_000_000);
            $result = [
                'status' => 'down',
                'latency_ms' => $latency,
                'message' => 'Base URL is not configured.',
                'balance' => null,
                'details' => ['error' => 'Missing base_url'],
            ];

            $this->persistHealth($gateway, $result);

            return $result;
        }

        try {
            $driverInstance = Sms::usingGateway($gateway)->driver($driver);
            $balance = null;

            if ($driverInstance instanceof SupportsDashboard) {
                try {
                    $dashboard = $driverInstance->getDashboardData();
                    $walletCard = collect($dashboard->cards)->firstWhere('label', 'Wallet Balance');
                    $balance = $walletCard?->value ?? null;
                } catch (Throwable) {
                    // ignore balance check failure and proceed with reachability
                }
            }

            // Test HTTP reachability of base endpoint
            $cleanBase = rtrim((string) $baseUrl, '/');
            $response = Http::timeout($timeout)
                ->withoutVerifying()
                ->get($cleanBase);

            $latency = (int) ((hrtime(true) - $start) / 1_000_000);
            $httpCode = $response->status();

            // Status 200-499 indicates the server is alive and responding to requests
            $status = $httpCode < 500 ? ($latency > 3000 ? 'degraded' : 'healthy') : 'down';
            $message = $status === 'healthy'
                ? "Provider is reachable and responding (HTTP {$httpCode}, {$latency}ms)."
                : ($status === 'degraded' ? "Provider responded with high latency ({$latency}ms)." : "Provider returned HTTP {$httpCode}.");

            $result = [
                'status' => $status,
                'latency_ms' => $latency,
                'message' => $message,
                'balance' => $balance,
                'details' => [
                    'http_status' => $httpCode,
                    'driver' => $driver,
                    'latency_ms' => $latency,
                ],
            ];

            $this->persistHealth($gateway, $result);

            return $result;
        } catch (Throwable $e) {
            $latency = (int) ((hrtime(true) - $start) / 1_000_000);
            $result = [
                'status' => 'down',
                'latency_ms' => $latency,
                'message' => 'Connection failed: '.$e->getMessage(),
                'balance' => null,
                'details' => [
                    'error' => $e->getMessage(),
                    'driver' => $driver,
                ],
            ];

            $this->persistHealth($gateway, $result);

            return $result;
        }
    }

    /**
     * Check all active gateways and return results.
     *
     * @return array<int, array<string, mixed>>
     */
    public function checkAll(): array
    {
        $results = [];
        $gateways = Gateway::all();

        foreach ($gateways as $gateway) {
            $results[$gateway->id] = $this->checkGateway($gateway);
        }

        return $results;
    }

    private function persistHealth(Gateway $gateway, array $result): void
    {
        $gateway->updateQuietly([
            'health_status' => $result['status'],
            'last_health_check_at' => now(),
            'last_health_latency_ms' => $result['latency_ms'],
            'last_health_response' => [
                'message' => $result['message'],
                'balance' => $result['balance'],
                'details' => $result['details'],
            ],
        ]);
    }
}

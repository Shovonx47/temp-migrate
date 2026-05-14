<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Deposit;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PipraPayDriver implements PaymentGateway
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
    ) {}

    /**
     * @throws ConnectionException
     */
    public function initiate(Deposit $deposit): RedirectResponse
    {
        $response = Http::withHeader('MHS-PIPRAPAY-API-KEY', $this->apiKey)
            ->post("{$this->baseUrl}/api/checkout/redirect", [
                'full_name' => $deposit->user->name,
                'email_address' => $deposit->user->email,
                'mobile_number' => $deposit->user->phone?->formatE164() ?? '01700000000',
                'amount' => (string) $deposit->amount,
                'currency' => $deposit->currency,
                'metadata' => json_encode([
                    'deposit_id' => $deposit->id,
                    'user_id' => $deposit->user_id,
                ]),
                'return_url' => route('payments.callback', ['gateway' => 'piprapay']),
                'webhook_url' => route('payments.webhook', ['gateway' => 'piprapay']),
            ]);

        if ($response->failed()) {
            $error = $response->json('error.message', 'Unknown error');

            throw new RuntimeException("PipraPay checkout failed: {$error}");
        }

        $data = $response->json();

        if (empty($data['pp_id'])) {
            throw new RuntimeException('PipraPay checkout did not return a pp_id.');
        }

        $deposit->update([
            'transaction_id' => $data['pp_id'],
            'meta' => $data,
        ]);

        return redirect()->away($data['pp_url']);
    }

    public function verify(string $ppId): array
    {
        $response = Http::withHeader('MHS-PIPRAPAY-API-KEY', $this->apiKey)
            ->post("{$this->baseUrl}/api/verify-payment", [
                'pp_id' => $ppId,
            ]);

        if ($response->failed()) {
            $error = $response->json('error.message', 'Unknown error');

            throw new RuntimeException("PipraPay verify failed: {$error}");
        }

        return $response->json();
    }

    public function handleWebhook(Request $request): array
    {
        $data = $request->validate([
            'pp_id' => ['required', 'string'],
            'status' => ['required', 'string'],
            'amount' => ['required'],
            'currency' => ['nullable', 'string'],
            'transaction_id' => ['nullable', 'string'],
            'metadata' => ['nullable'],
        ]);

        return $data;
    }

    public function refund(string $ppId): array
    {
        $response = Http::withHeader('MHS-PIPRAPAY-API-KEY', $this->apiKey)
            ->post("{$this->baseUrl}/api/refund-payment", [
                'pp_id' => $ppId,
            ]);

        if ($response->failed()) {
            $error = $response->json('error.message', 'Unknown error');

            throw new RuntimeException("PipraPay refund failed: {$error}");
        }

        return $response->json();
    }
}

<?php

namespace App\Livewire\SenderCli;

use App\Models\Gateway;
use App\Models\GatewayOperatorRate;
use App\Models\Operator;
use App\Models\Sender;
use Livewire\Component;
use Livewire\WithPagination;

class SenderResolution extends Component
{
    use WithPagination;

    // Simulation inputs
    public string $simSenderInput = '';

    public string $simRecipientPhone = '01712345678';

    public ?array $resolutionOutput = null;

    public string $search = '';

    public function mount(): void
    {
        $firstSender = Sender::first();
        if ($firstSender) {
            $this->simSenderInput = $firstSender->sender_id;
        } else {
            $this->simSenderInput = 'WHITEINFO';
        }
        $this->resolveRoute();
    }

    public function updated($propertyName): void
    {
        if (in_array($propertyName, ['simSenderInput', 'simRecipientPhone'])) {
            $this->resolveRoute();
        }
    }

    public function resolveRoute(): void
    {
        $senderInput = trim($this->simSenderInput);
        $phoneInput = trim($this->simRecipientPhone);

        if (empty($senderInput) || empty($phoneInput)) {
            $this->resolutionOutput = null;

            return;
        }

        // 1. Detect Sender & Type
        $senderModel = Sender::with('gateway')->where('sender_id', $senderInput)->first();
        $isMasking = true;
        $senderTypeLabel = 'Masking (Brand Alphanumeric)';

        if ($senderModel) {
            $isMasking = $senderModel->type?->isMasking() ?? true;
            $senderTypeLabel = $senderModel->type?->getLabel() ?? 'Masking';
            $gateway = $senderModel->gateway ?? Gateway::first();
        } else {
            // Determine type heuristically
            if (is_numeric($senderInput) && strlen($senderInput) >= 10) {
                $isMasking = false;
                $senderTypeLabel = 'Non-Masking (Numeric CLI)';
            } elseif (is_numeric($senderInput)) {
                $isMasking = true;
                $senderTypeLabel = 'Masking (Shortcode)';
            }
            $gateway = Gateway::first();
        }

        // 2. Identify Operator from Recipient Phone
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneInput);
        if (str_starts_with($cleanPhone, '880')) {
            $nationalNumber = substr($cleanPhone, 2);
        } else {
            $nationalNumber = $cleanPhone;
        }

        $detectedOperatorName = 'Unknown Operator';
        $detectedPrefix = substr($nationalNumber, 0, 3);
        $matchedOperator = null;

        $operators = Operator::with('country')->get();
        foreach ($operators as $op) {
            $codes = (array) ($op->codes ?? []);
            foreach ($codes as $code) {
                if (str_starts_with($nationalNumber, (string) $code) || str_starts_with($nationalNumber, '0'.(string) $code)) {
                    $matchedOperator = $op;
                    $detectedOperatorName = $op->name;
                    break 2;
                }
            }
        }

        if (! $matchedOperator) {
            if (str_starts_with($nationalNumber, '017') || str_starts_with($nationalNumber, '013') || str_starts_with($nationalNumber, '17') || str_starts_with($nationalNumber, '13')) {
                $detectedOperatorName = 'Grameenphone';
            } elseif (str_starts_with($nationalNumber, '018') || str_starts_with($nationalNumber, '18')) {
                $detectedOperatorName = 'Robi';
            } elseif (str_starts_with($nationalNumber, '019') || str_starts_with($nationalNumber, '014') || str_starts_with($nationalNumber, '19') || str_starts_with($nationalNumber, '14')) {
                $detectedOperatorName = 'Banglalink';
            } elseif (str_starts_with($nationalNumber, '015') || str_starts_with($nationalNumber, '15')) {
                $detectedOperatorName = 'Teletalk';
            } elseif (str_starts_with($nationalNumber, '016') || str_starts_with($nationalNumber, '16')) {
                $detectedOperatorName = 'Airtel';
            }
            $matchedOperator = Operator::where('name', 'like', "%{$detectedOperatorName}%")->first();
        }

        // 3. Resolve Operator Rate under Gateway
        $rateRecord = null;
        if ($gateway && $matchedOperator) {
            $rateRecord = GatewayOperatorRate::where('gateway_id', $gateway->id)
                ->where('operator_id', $matchedOperator->id)
                ->first();
        }

        $baseCost = $matchedOperator ? ($isMasking ? $matchedOperator->masking_price : $matchedOperator->non_masking_price) : 0.25;
        $gatewayRate = $rateRecord ? ($isMasking ? $rateRecord->masking_price : $rateRecord->non_masking_price) : ($isMasking ? 0.45 : 0.23);

        $this->resolutionOutput = [
            'senderId' => $senderInput,
            'isRegistered' => (bool) $senderModel,
            'senderType' => $senderTypeLabel,
            'isMasking' => $isMasking,
            'approvalStatus' => $senderModel ? ($senderModel->approval_status?->value ?? 'approved') : 'unregistered',
            'gatewayName' => $gateway?->name ?? 'Default Gateway (Infozillion)',
            'gatewayDriver' => $gateway?->driver?->value ?? 'infozillion',
            'recipientPhone' => $phoneInput,
            'operatorName' => $detectedOperatorName,
            'operatorBaseCost' => $baseCost ?? 0.25,
            'gatewayChargedRate' => $gatewayRate ?? 0.45,
            'routeHealth' => 'Optimal (Latency ~80ms)',
            'status' => 'Route Active',
        ];
    }

    public function selectSender(string $senderId): void
    {
        $this->simSenderInput = $senderId;
        $this->resolveRoute();
    }

    public function render()
    {
        $sendersQuery = Sender::query()
            ->with(['gateway', 'users'])
            ->withCount('messages');

        if ($this->search !== '') {
            $sendersQuery->where(function ($q) {
                $q->where('sender_id', 'like', "%{$this->search}%")
                    ->orWhereHas('gateway', fn ($gw) => $gw->where('name', 'like', "%{$this->search}%"));
            });
        }

        $senders = $sendersQuery->orderByDesc('id')->paginate(10);

        return view('livewire.sender-cli.sender-resolution', [
            'senders' => $senders,
            'gateways' => Gateway::all(),
        ]);
    }
}

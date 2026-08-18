<?php

namespace App\Livewire\SenderCli;

use Livewire\Component;

class SenderPolicy extends Component
{
    // Interactive Policy Validator state
    public string $testSenderId = '';

    public string $testSenderType = 'masking'; // 'masking' | 'numeric'

    public string $testMessageBody = '';

    public array $validationResults = [];

    public function mount(): void
    {
        $this->validatePolicy();
    }

    public function updated($propertyName): void
    {
        $this->validatePolicy();
    }

    public function validatePolicy(): void
    {
        $sender = trim($this->testSenderId);
        $type = $this->testSenderType;
        $body = $this->testMessageBody;

        $errors = [];
        $warnings = [];
        $passes = [];

        // 1. Sender ID validation
        if (empty($sender)) {
            $this->validationResults = [
                'hasInput' => false,
                'status' => 'idle',
                'errors' => [],
                'warnings' => [],
                'passes' => [],
                'charCount' => 0,
                'partCount' => 1,
                'isUnicode' => false,
            ];

            return;
        }

        if ($type === 'masking') {
            // Masking length check: 3 to 11 characters
            $len = strlen($sender);
            if ($len < 3 || $len > 11) {
                $errors[] = "Masking Sender ID length must be between 3 and 11 characters (current: {$len}).";
            } else {
                $passes[] = "Length compliant ({$len} characters, allowed: 3-11).";
            }

            // Allowed characters: Alphanumeric and spaces only
            if (! preg_match('/^[a-zA-Z0-9 ]+$/', $sender)) {
                $errors[] = 'Masking Sender ID must contain only alphanumeric characters (A-Z, a-z, 0-9) and spaces.';
            } else {
                $passes[] = 'Character set compliant (Alphanumeric).';
            }

            // Cannot be purely numeric
            if (is_numeric($sender)) {
                $errors[] = 'A Masking Sender ID cannot be purely numeric digits. Use Non-Masking for numeric CLIs.';
            }

            // Restricted keywords check
            $restricted = ['bkash', 'nagad', 'rocket', 'upay', 'btrc', 'police', 'rab', 'army', 'govt', 'bangladesh', 'corona', 'emergency', 'otp', 'admin'];
            foreach ($restricted as $kw) {
                if (stripos($sender, $kw) !== false) {
                    $warnings[] = "Sender ID contains restricted term '{$kw}'. Verification documentation / NOC required.";
                }
            }
        } else {
            // Non-Masking Numeric validation: must be valid 11 digit MSISDN or shortcode
            $clean = preg_replace('/[^0-9]/', '', $sender);
            if (strlen($clean) === 11 && str_starts_with($clean, '01')) {
                $passes[] = "Valid Bangladesh MSISDN format ({$clean}).";
            } elseif (strlen($clean) >= 4 && strlen($clean) <= 6) {
                $passes[] = "Valid Shortcode format ({$clean}).";
            } else {
                $errors[] = 'Non-masking CLI must be an 11-digit mobile number starting with 01 (or 4-6 digit approved shortcode).';
            }
        }

        // 2. Message Body Analysis
        $isUnicode = false;
        if (! empty($body)) {
            // Check if unicode
            $isUnicode = (bool) preg_match('/[^\x00-\x7F]/', $body);
            $bodyLen = mb_strlen($body);

            if ($isUnicode) {
                $partLimit = 70;
                $concatLimit = 67;
                $parts = $bodyLen <= $partLimit ? 1 : (int) ceil($bodyLen / $concatLimit);
                $passes[] = "Unicode detected (Bengali/UTF-16). Max {$partLimit} chars for 1 part, {$concatLimit} chars/part for multipart.";
            } else {
                $partLimit = 160;
                $concatLimit = 153;
                $parts = $bodyLen <= $partLimit ? 1 : (int) ceil($bodyLen / $concatLimit);
                $passes[] = "Standard GSM-7 encoding (ASCII). Max {$partLimit} chars for 1 part, {$concatLimit} chars/part for multipart.";
            }

            if ($parts > 5) {
                $warnings[] = "Message requires {$parts} parts. High billing cost per recipient.";
            }
        } else {
            $bodyLen = 0;
            $parts = 1;
        }

        $status = count($errors) > 0 ? 'rejected' : (count($warnings) > 0 ? 'warning' : 'compliant');

        $this->validationResults = [
            'hasInput' => true,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings,
            'passes' => $passes,
            'charCount' => $bodyLen,
            'partCount' => $parts,
            'isUnicode' => $isUnicode,
        ];
    }

    public function render()
    {
        return view('livewire.sender-cli.sender-policy');
    }
}

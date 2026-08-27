<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Mail\SendOtpMail;
use App\Models\EmailGateway;
use App\Models\Gateway;
use App\Models\User;
use App\Models\VerificationOtp;
use App\Services\Sms\Data\SmsRequest;
use App\Services\Sms\SmsManager;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Propaganistas\LaravelPhone\PhoneNumber;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Throwable;

class OtpVerificationService
{
    public const int OTP_EXPIRY_MINUTES = 10;
    public const int COOLDOWN_SECONDS = 60;
    public const int MAX_ATTEMPTS = 5;
    public const int MAX_HOURLY_RESENDS = 5;

    public function __construct(
        protected SmsManager $smsManager
    ) {}

    /**
     * Send OTP to an Email address with Priority & Automatic Failover.
     */
    public function sendEmailOtp(string $email): array
    {
        $email = strtolower(trim($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email address provided.',
            ];
        }

        if (User::where('email', $email)->exists()) {
            return [
                'success' => false,
                'message' => 'This email address is already registered.',
            ];
        }

        $rateLimitCheck = $this->checkRateLimit($email, 'email');
        if (! $rateLimitCheck['allowed']) {
            return [
                'success' => false,
                'message' => $rateLimitCheck['message'],
                'cooldown_seconds' => $rateLimitCheck['cooldown_seconds'] ?? 0,
            ];
        }

        $otp = (string) random_int(100000, 999999);
        $this->storeOtp($email, 'email', $otp);

        $sent = $this->dispatchEmailWithFailover($email, $otp);

        if (! $sent) {
            return [
                'success' => false,
                'message' => 'Failed to deliver verification email through all configured mail servers. Please try again later.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Verification code sent to your email.',
            'cooldown_seconds' => self::COOLDOWN_SECONDS,
        ];
    }

    /**
     * Dispatch email trying configured gateways in Priority order with Automatic Failover.
     */
    protected function dispatchEmailWithFailover(string $email, string $otp): bool
    {
        $gateways = EmailGateway::active()->ordered()->get();
        $mailable = new SendOtpMail($otp, self::OTP_EXPIRY_MINUTES);

        if ($gateways->isNotEmpty()) {
            foreach ($gateways as $gateway) {
                try {
                    $this->sendViaEmailGateway($gateway, $email, $mailable);

                    Log::info("Email OTP delivered successfully via priority gateway [{$gateway->name}] (Priority: {$gateway->priority})", [
                        'gateway_id' => $gateway->id,
                        'gateway_name' => $gateway->name,
                        'priority' => $gateway->priority,
                        'recipient' => $email,
                    ]);

                    return true;
                } catch (Throwable $e) {
                    Log::warning("Email OTP delivery failed via gateway [{$gateway->name}] (Priority: {$gateway->priority}): {$e->getMessage()}. Attempting next gateway...", [
                        'gateway_id' => $gateway->id,
                        'gateway_name' => $gateway->name,
                        'priority' => $gateway->priority,
                        'recipient' => $email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Fallback to default Laravel Mailer (e.g. SMTP in .env or log driver)
        try {
            Mail::to($email)->send($mailable);

            Log::info("Email OTP delivered via default system mailer", [
                'recipient' => $email,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error("Email OTP delivery failed via default mailer: {$e->getMessage()}", [
                'recipient' => $email,
                'error' => $e->getMessage(),
            ]);

            return app()->environment('local', 'testing');
        }
    }

    /**
     * Send email via a specific EmailGateway instance.
     */
    protected function sendViaEmailGateway(EmailGateway $gateway, string $email, SendOtpMail $mailable): void
    {
        $tls = in_array(strtolower((string) $gateway->encryption), ['tls', 'ssl'], true);
        $transport = new EsmtpTransport(
            host: $gateway->host,
            port: $gateway->port,
            tls: $tls
        );

        if (filled($gateway->username)) {
            $transport->setUsername($gateway->username);
            $transport->setPassword($gateway->password ?? '');
        }

        $symfonyMailer = new SymfonyMailer($transport);
        $mailer = new Mailer(
            name: 'dynamic_'.$gateway->id,
            views: app('view'),
            transport: $symfonyMailer,
            events: app('events')
        );

        if (filled($gateway->from_address)) {
            $mailer->alwaysFrom($gateway->from_address, $gateway->from_name ?: config('mail.from.name'));
        }

        $mailer->to($email)->send($mailable);
    }

    /**
     * Send OTP to a Mobile Number with Priority & Automatic Failover.
     */
    public function sendSmsOtp(string $phone): array
    {
        $normalizedPhone = $this->normalizePhoneNumber($phone);

        if (! $normalizedPhone) {
            return [
                'success' => false,
                'message' => 'Invalid mobile number. Please enter a valid Bangladesh mobile number.',
            ];
        }

        if (User::where('phone', $normalizedPhone)->exists()) {
            return [
                'success' => false,
                'message' => 'This mobile number is already registered.',
            ];
        }

        $rateLimitCheck = $this->checkRateLimit($normalizedPhone, 'sms');
        if (! $rateLimitCheck['allowed']) {
            return [
                'success' => false,
                'message' => $rateLimitCheck['message'],
                'cooldown_seconds' => $rateLimitCheck['cooldown_seconds'] ?? 0,
            ];
        }

        $otp = (string) random_int(100000, 999999);
        $this->storeOtp($normalizedPhone, 'sms', $otp);

        $smsContent = "Your SMS WhiteInfo verification code is: {$otp}. Valid for ".self::OTP_EXPIRY_MINUTES." minutes.";
        $sms = new SmsRequest(
            to: $normalizedPhone,
            body: $smsContent,
        );

        $sent = $this->dispatchSmsWithFailover($normalizedPhone, $sms);

        if (! $sent) {
            return [
                'success' => false,
                'message' => 'Failed to deliver SMS verification code through all configured SMS routes. Please try again later.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Verification code sent to your mobile number via SMS.',
            'cooldown_seconds' => self::COOLDOWN_SECONDS,
        ];
    }

    /**
     * Dispatch SMS trying configured gateways in Priority order with Automatic Failover.
     */
    protected function dispatchSmsWithFailover(string $phone, SmsMessage $sms): bool
    {
        $gateways = Gateway::forOtp()->ordered()->get();

        if ($gateways->isNotEmpty()) {
            foreach ($gateways as $gateway) {
                try {
                    $this->smsManager
                        ->usingGateway($gateway)
                        ->driver($gateway->driver->value)
                        ->send($sms);

                    Log::info("SMS OTP delivered successfully via priority gateway [{$gateway->name}] (Priority: {$gateway->priority}, Driver: {$gateway->driver->value})", [
                        'gateway_id' => $gateway->id,
                        'gateway_name' => $gateway->name,
                        'priority' => $gateway->priority,
                        'driver' => $gateway->driver->value,
                        'recipient' => $phone,
                    ]);

                    return true;
                } catch (Throwable $e) {
                    Log::warning("SMS OTP delivery failed via gateway [{$gateway->name}] (Priority: {$gateway->priority}): {$e->getMessage()}. Attempting next gateway...", [
                        'gateway_id' => $gateway->id,
                        'gateway_name' => $gateway->name,
                        'priority' => $gateway->priority,
                        'recipient' => $phone,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Fallback to default configured driver (e.g. 'log' driver in local/testing)
        try {
            $this->smsManager->driver()->send($sms);

            Log::info('SMS OTP delivered via default system SMS driver', [
                'recipient' => $phone,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error("SMS OTP delivery failed via default driver: {$e->getMessage()}", [
                'recipient' => $phone,
                'error' => $e->getMessage(),
            ]);

            return app()->environment('local', 'testing');
        }
    }

    /**
     * Verify submitted OTP.
     */
    public function verifyOtp(string $identifier, string $type, string $otp): array
    {
        $identifier = $type === 'email' ? strtolower(trim($identifier)) : $this->normalizePhoneNumber($identifier);
        $otp = trim($otp);

        if (! $identifier) {
            return [
                'success' => false,
                'message' => 'Invalid identifier.',
            ];
        }

        $record = VerificationOtp::where('identifier', $identifier)
            ->where('type', $type)
            ->latest('id')
            ->first();

        if (! $record) {
            return [
                'success' => false,
                'message' => 'No verification code requested for this '.($type === 'email' ? 'email' : 'phone number').'.',
            ];
        }

        if ($record->isExpired()) {
            return [
                'success' => false,
                'message' => 'Verification code has expired. Please request a new code.',
            ];
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            return [
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new verification code.',
            ];
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            $record->increment('attempts');
            $remaining = self::MAX_ATTEMPTS - $record->attempts;

            return [
                'success' => false,
                'message' => $remaining > 0
                    ? "Incorrect code. {$remaining} attempts remaining."
                    : 'Too many failed attempts. Please request a new verification code.',
            ];
        }

        $token = Str::random(60);
        $record->update([
            'verified_at' => now(),
            'token' => $token,
        ]);

        return [
            'success' => true,
            'message' => ucfirst($type).' verified successfully.',
            'token' => $token,
        ];
    }

    /**
     * Check if an identifier is verified with a valid proof token.
     */
    public function isVerified(string $identifier, string $type, ?string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $identifier = $type === 'email' ? strtolower(trim($identifier)) : $this->normalizePhoneNumber($identifier);

        if (! $identifier) {
            return false;
        }

        return VerificationOtp::where('identifier', $identifier)
            ->where('type', $type)
            ->where('token', $token)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subHours(2))
            ->exists();
    }

    /**
     * Normalize phone number to E.164 format.
     */
    public function normalizePhoneNumber(string $phone): ?string
    {
        $phone = trim($phone);

        try {
            return (string) (new PhoneNumber($phone, 'BD'))->formatE164();
        } catch (Throwable) {
            $cleaned = preg_replace('/[^\d+]/', '', $phone);
            if (str_starts_with($cleaned, '+880') && strlen($cleaned) === 14) {
                return $cleaned;
            }
            if (str_starts_with($cleaned, '880') && strlen($cleaned) === 13) {
                return '+'.$cleaned;
            }
            if (str_starts_with($cleaned, '01') && strlen($cleaned) === 11) {
                return '+88'.$cleaned;
            }

            return null;
        }
    }

    protected function checkRateLimit(string $identifier, string $type): array
    {
        $recent = VerificationOtp::where('identifier', $identifier)
            ->where('type', $type)
            ->latest('id')
            ->first();

        if ($recent && $recent->last_sent_at) {
            $secondsSinceLast = now()->diffInSeconds($recent->last_sent_at);
            if ($secondsSinceLast < self::COOLDOWN_SECONDS) {
                $cooldownLeft = self::COOLDOWN_SECONDS - $secondsSinceLast;

                return [
                    'allowed' => false,
                    'message' => "Please wait {$cooldownLeft} seconds before requesting a new code.",
                    'cooldown_seconds' => $cooldownLeft,
                ];
            }
        }

        $hourlyCount = VerificationOtp::where('identifier', $identifier)
            ->where('type', $type)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($hourlyCount >= self::MAX_HOURLY_RESENDS) {
            return [
                'allowed' => false,
                'message' => 'Too many requests. Please try again in an hour.',
                'cooldown_seconds' => 3600,
            ];
        }

        return ['allowed' => true];
    }

    protected function storeOtp(string $identifier, string $type, string $otp): VerificationOtp
    {
        return VerificationOtp::create([
            'identifier' => $identifier,
            'type' => $type,
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'last_sent_at' => now(),
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ]);
    }
}

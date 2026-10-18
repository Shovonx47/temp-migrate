<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\PaymentWebhook;
use App\Models\User;

class PaymentWebhookPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->type === UserType::Superman;
    }

    public function view(User $user, PaymentWebhook $paymentWebhook): bool
    {
        return $user->type === UserType::Superman;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PaymentWebhook $paymentWebhook): bool
    {
        return false;
    }

    public function delete(User $user, PaymentWebhook $paymentWebhook): bool
    {
        return false;
    }

    public function restore(User $user, PaymentWebhook $paymentWebhook): bool
    {
        return false;
    }

    public function forceDelete(User $user, PaymentWebhook $paymentWebhook): bool
    {
        return false;
    }
}

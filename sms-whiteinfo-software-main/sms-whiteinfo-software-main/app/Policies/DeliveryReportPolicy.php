<?php

namespace App\Policies;

use App\Models\DeliveryReport;
use App\Models\User;

class DeliveryReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DeliveryReport $deliveryReport): bool
    {
        return $deliveryReport->message?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, DeliveryReport $deliveryReport): bool
    {
        return false;
    }

    public function delete(User $user, DeliveryReport $deliveryReport): bool
    {
        return false;
    }

    public function restore(User $user, DeliveryReport $deliveryReport): bool
    {
        return false;
    }

    public function forceDelete(User $user, DeliveryReport $deliveryReport): bool
    {
        return false;
    }
}

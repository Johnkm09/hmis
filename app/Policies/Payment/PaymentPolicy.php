<?php

namespace App\Policies\Payment;

use App\Models\Payment\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view payments');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('view payments');
    }

    public function create(User $user): bool
    {
        return $user->can('create payments');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->can('update payments');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}

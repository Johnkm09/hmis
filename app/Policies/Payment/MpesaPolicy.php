<?php

namespace App\Policies\Payment;

use App\Models\Payment\Payment;
use App\Models\User;

class MpesaPolicy
{
    public function create(User $user): bool
    {
        return $user->can('create payments');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('view payments');
    }
}

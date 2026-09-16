<?php

namespace App\Policies\Payment;

use App\Models\Payment\Refund;
use App\Models\User;

class RefundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view refunds');
    }

    public function view(User $user, Refund $refund): bool
    {
        return $user->can('view refunds');
    }

    public function create(User $user): bool
    {
        return $user->can('create refunds');
    }

    public function update(User $user, Refund $refund): bool
    {
        return $user->can('update refunds');
    }

    public function delete(User $user, Refund $refund): bool
    {
        return false;
    }
}

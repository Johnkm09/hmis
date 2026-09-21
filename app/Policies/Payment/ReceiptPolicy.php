<?php

namespace App\Policies\Payment;

use App\Models\Payment\Receipt;
use App\Models\User;

class ReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view receipts');
    }

    public function view(User $user, Receipt $receipt): bool
    {
        return $user->can('view receipts');
    }

    public function create(User $user): bool
    {
        return $user->can('create receipts');
    }

    public function update(User $user, Receipt $receipt): bool
    {
        return $user->can('update receipts');
    }

    public function delete(User $user, Receipt $receipt): bool
    {
        return false;
    }
}

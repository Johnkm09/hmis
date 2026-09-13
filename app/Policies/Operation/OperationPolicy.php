<?php

namespace App\Policies\Operation;

use App\Models\User;

class OperationPolicy
{
    /**
     * Allow viewing operation history.
     */
    public function view(User $user): bool
    {
        return $user->can('view operations');
    }

    /**
     * Allow creating check-in, walk-in and check-out operations.
     */
    public function create(User $user): bool
    {
        return $user->can('create operations');
    }
}

<?php

namespace App\Policies\Folio;

use App\Models\Folio\FolioCharge;
use App\Models\User;

class FolioChargePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view folio charges');
    }

    public function view(User $user, FolioCharge $folioCharge): bool
    {
        return $user->can('view folio charges');
    }

    public function create(User $user): bool
    {
        return $user->can('create folio charges');
    }

    public function update(User $user, FolioCharge $folioCharge): bool
    {
        return $user->can('update folio charges');
    }
}

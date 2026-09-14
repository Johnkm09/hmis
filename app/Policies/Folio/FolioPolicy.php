<?php

namespace App\Policies\Folio;

use App\Models\Folio\Folio;
use App\Models\User;

class FolioPolicy
{
    /**
     * Determine whether the user can view any folios.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view folios');
    }

    /**
     * Determine whether the user can view the folio.
     */
    public function view(User $user, Folio $folio): bool
    {
        return $user->can('view folios');
    }

    /**
     * Determine whether the user can create a folio.
     */
    public function create(User $user): bool
    {
        return $user->can('create folios');
    }

    /**
     * Determine whether the user can update the folio.
     */
    public function update(User $user, Folio $folio): bool
    {
        return $user->can('update folios');
    }
}

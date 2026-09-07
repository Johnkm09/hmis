<?php

namespace App\Policies\Guest;

use App\Models\Guest\Guest;
use App\Models\User;

class GuestPolicy
{
    /**
     * Determine whether the user can view any guests.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view guests');
    }

    /**
     * Determine whether the user can view the guest.
     */
    public function view(User $user, Guest $guest): bool
    {
        return $user->can('view guests');
    }

    /**
     * Determine whether the user can create guests.
     */
    public function create(User $user): bool
    {
        return $user->can('create guests');
    }

    /**
     * Determine whether the user can update the guest.
     */
    public function update(User $user, Guest $guest): bool
    {
        return $user->can('update guests');
    }

    /**
     * Determine whether the user can delete the guest.
     */
    public function delete(User $user, Guest $guest): bool
    {
        return $user->can('delete guests');
    }

    /**
     * Determine whether the user can restore the guest.
     */
    public function restore(User $user, Guest $guest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the guest.
     */
    public function forceDelete(User $user, Guest $guest): bool
    {
        return false;
    }
}

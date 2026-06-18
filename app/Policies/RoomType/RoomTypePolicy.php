<?php

namespace App\Policies\RoomType;

use App\Models\RoomType\RoomType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RoomTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role,[
            'super_admin',
            'manager',
            'receptionist',
            'user'
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RoomType $roomType): bool
    {
        return in_array($user->role,[
            'super_admin',
            'manager',
            'receptionist',
            'user'
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role,[
            'super_admin',
            'manager'
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RoomType $roomType): bool
    {
        return in_array($user->role,[
            'super_admin',
            'manager'
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RoomType $roomType): bool
    {
        return in_array($user->role,[
            'super_admin',
            'manager'
        ]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RoomType $roomType): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RoomType $roomType): bool
    {
        return false;
    }
}

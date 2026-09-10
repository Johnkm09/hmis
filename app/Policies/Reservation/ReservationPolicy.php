<?php

namespace App\Policies\Reservation;

use App\Models\Reservation\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view reservations');
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $user->can('view reservations');
    }

    public function create(User $user): bool
    {
        return $user->can('create reservations');
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $user->can('update reservations');
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return $user->can('delete reservations');
    }

    public function restore(User $user, Reservation $reservation): bool
    {
        return false;
    }

    public function forceDelete(User $user, Reservation $reservation): bool
    {
        return false;
    }
}

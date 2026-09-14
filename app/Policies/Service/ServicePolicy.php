<?php

namespace App\Policies\Service;

use App\Models\Service\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view services');
    }

    public function view(User $user, Service $service): bool
    {
        return $user->can('view services');
    }

    public function create(User $user): bool
    {
        return $user->can('create services');
    }

    public function update(User $user, Service $service): bool
    {
        return $user->can('update services');
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->can('delete services');
    }
}

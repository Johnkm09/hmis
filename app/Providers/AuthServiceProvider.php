<?php

namespace App\Providers;

use App\Models\RoomType\RoomType;
use App\Policies\RoomType\RoomTypePolicy;
use App\Models\Room\Room;
use App\Models\Guest\Guest;
use App\Policies\Guest\GuestPolicy;
use App\Policies\Room\RoomPolicy;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        RoomType::class => RoomTypePolicy::class,
        Room::class => RoomPolicy::class,
        Guest::class => GuestPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

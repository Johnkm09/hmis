<?php

namespace App\Providers;

use App\Models\RoomType\RoomType;
use App\Policies\RoomType\RoomTypePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        RoomType::class => RoomTypePolicy::class,
    ];

    public function boot():void
    {
        $this->registerPolicies();
    }
}

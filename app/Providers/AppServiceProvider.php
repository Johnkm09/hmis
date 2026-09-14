<?php

namespace App\Providers;

use App\Repositories\RoomType\RoomTypeInterface;
use App\Repositories\RoomType\RoomTypeRepository;
use App\Repositories\Room\RoomRepositoryInterface;
use App\Repositories\Room\RoomRepository;
use App\Repositories\Guest\GuestRepository;
use App\Repositories\Guest\GuestRepositoryInterface;
use App\Repositories\Reservation\ReservationInterface;
use App\Repositories\Reservation\ReservationRepository;
use App\Repositories\Contracts\OperationInterface;
use App\Repositories\Contracts\OperationRepository;
use App\Repositories\Service\ServiceRepositoryInterface;
use App\Repositories\Service\ServiceRepository;
use App\Repositories\Folio\FolioRepositoryInterface;
use App\Repositories\Folio\FolioRepository;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RoomTypeInterface::class, RoomTypeRepository::class);
        $this->app->bind(RoomRepositoryInterface::class, RoomRepository::class);
        $this->app->bind(GuestRepositoryInterface::class, GuestRepository::class);
        $this->app->bind(ReservationInterface::class, ReservationRepository::class);
        $this->app->bind(OperationInterface::class, OperationRepository::class);
        $this->app->bind(ServiceRepositoryInterface::class, ServiceRepository::class);
        $this->app->bind(FolioRepositoryInterface::class, FolioRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}

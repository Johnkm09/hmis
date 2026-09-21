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
use App\Repositories\Folio\FolioChargeRepository;
use App\Repositories\Folio\FolioChargeRepositoryInterface;
use App\Repositories\Payment\PaymentRepository;
use App\Repositories\Payment\PaymentRepositoryInterface;
use App\Repositories\Payment\RefundRepository;
use App\Repositories\Payment\RefundRepositoryInterface;
use App\Repositories\Payment\InvoiceRepository;
use App\Repositories\Payment\InvoiceRepositoryInterface;
use App\Repositories\Payment\ReceiptRepository;
use App\Repositories\Payment\ReceiptRepositoryInterface;
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
        $this->app->bind(FolioChargeRepositoryInterface::class, FolioChargeRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->bind(RefundRepositoryInterface::class, RefundRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->bind(ReceiptRepositoryInterface::class, ReceiptRepository::class);
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

<?php

namespace App\Providers;

use App\Models\RoomType\RoomType;
use App\Policies\RoomType\RoomTypePolicy;
use App\Models\Room\Room;
use App\Models\Guest\Guest;
use App\Policies\Guest\GuestPolicy;
use App\Policies\Room\RoomPolicy;
use App\Models\Reservation\Reservation;
use App\Policies\Reservation\ReservationPolicy;
use App\Policies\Operation\OperationPolicy;
use App\Models\Contract\Operation;
use App\Models\Service\Service;
use App\Policies\Service\ServicePolicy;
use App\Models\Folio\Folio;
use App\Policies\Folio\FolioPolicy;
use App\Models\Folio\FolioCharge;
use App\Policies\Folio\FolioChargePolicy;
use App\Models\Payment\Payment;
use App\Policies\Payment\PaymentPolicy;
use App\Models\Payment\Refund;
use App\Policies\Payment\RefundPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        RoomType::class => RoomTypePolicy::class,
        Room::class => RoomPolicy::class,
        Guest::class => GuestPolicy::class,
        Reservation::class => ReservationPolicy::class,
        Operation::class => OperationPolicy::class,
        Service::class => ServicePolicy::class,
        Folio::class => FolioPolicy::class,
        FolioCharge::class => FolioChargePolicy::class,
        Payment::class => PaymentPolicy::class,
        Refund::class => RefundPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

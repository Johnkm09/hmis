<?php

namespace App\Http\Controllers\Api\V1\Reservation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reservation\ReservationIndexRequest;
use App\Http\Requests\Api\V1\Reservation\ReservationRequest;
use App\Http\Requests\Api\V1\Reservation\UpdateReservationRequest;
use App\Http\Resources\Api\V1\Reservation\ReservationResource;
use App\Models\Reservation\Reservation;
use App\Services\ReservationService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReservationController extends Controller
{
    use AuthorizesRequests;

    protected ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    /**
     * Get all reservations
     *
     * @group Reservations(v1)
     * @authenticated
     * @queryParam filter[guest_id] integer Filter by guest ID. Example: 1
     * @queryParam filter[room_id] integer Filter by room ID. Example: 1
     * @queryParam filter[status] string Filter by reservation status. Example: pending
     * @queryParam filter[check_in_from] date Filter reservations from this check-in date. Example: 2026-09-10
     * @queryParam filter[check_in_to] date Filter reservations up to this check-in date. Example: 2026-09-30
     * @queryParam filter[check_out_from] date Filter reservations from this check-out date. Example: 2026-09-10
     * @queryParam filter[check_out_to] date Filter reservations up to this check-out date. Example: 2026-09-30
     * @queryParam filter[number_of_guests] integer Filter by number of guests. Example: 2
     * @queryParam sort string Sort reservations. Prefix with - for descending order. Example: -check_in
     * @queryParam per_page integer Number of reservations per page. Example: 10
     */
    public function index(ReservationIndexRequest $request)
    {
        $this->authorize('viewAny', Reservation::class);

        $reservations = $this->reservationService->getAll();

        return ApiResponse::success(
            ReservationResource::collection($reservations),
            'Reservations retrieved successfully',
            200,
            $reservations
        );
    }

    /**
     * Create a reservation
     *
     * @group Reservations(v1)
     * @authenticated
     * @bodyParam guest_id integer required The ID of the guest. Example: 1
     * @bodyParam room_id integer required The ID of the room. Example: 1
     * @bodyParam check_in date required The check-in date. Example: 2026-09-10
     * @bodyParam check_out date required The check-out date. Example: 2026-09-13
     * @bodyParam number_of_guests integer required Number of guests staying. Example: 2
     */
    public function store(ReservationRequest $request)
    {
        $this->authorize('create', Reservation::class);

        $reservation = $this->reservationService->create(
            $request->validated()
        );

        return ApiResponse::success(
            new ReservationResource($reservation),
            'Reservation created successfully',
            201
        );
    }

    /**
     * Get a single reservation
     *
     * @group Reservations(v1)
     * @authenticated
     * @urlParam id integer required The ID of the reservation. Example: 1
     */
    public function show(string $id)
    {
        $reservation = $this->reservationService->findById((int) $id);

        $this->authorize('view', $reservation);

        return ApiResponse::success(
            new ReservationResource($reservation),
            'Reservation retrieved successfully'
        );
    }

    /**
     * Update a reservation
     *
     * @group Reservations(v1)
     * @authenticated
     * @urlParam id integer required The ID of the reservation. Example: 1
     * @bodyParam guest_id integer The ID of the guest. Example: 1
     * @bodyParam room_id integer The ID of the room. Example: 1
     * @bodyParam check_in date The check-in date. Example: 2026-09-10
     * @bodyParam check_out date The check-out date. Example: 2026-09-13
     * @bodyParam number_of_guests integer Number of guests staying. Example: 2
     */
    public function update(UpdateReservationRequest $request, string $id)
    {
        $reservation = $this->reservationService->findById((int) $id);

        $this->authorize('update', $reservation);

        $reservation = $this->reservationService->update(
            (int) $id,
            $request->validated()
        );

        return ApiResponse::success(
            new ReservationResource($reservation),
            'Reservation updated successfully'
        );
    }

    /**
     * Delete a reservation
     *
     * @group Reservations(v1)
     * @authenticated
     * @urlParam id integer required The ID of the reservation. Example: 1
     */
    public function destroy(string $id)
    {
        $reservation = $this->reservationService->findById((int) $id);

        $this->authorize('delete', $reservation);

        $this->reservationService->delete((int) $id);

        return ApiResponse::success(
            [],
            'Reservation deleted successfully'
        );
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Operation;

use App\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Operation\CheckInRequest;
use App\Http\Requests\Api\V1\Operation\CheckOutRequest;
use App\Http\Requests\Api\V1\Operation\WalkInRequest;
use App\Http\Resources\Api\V1\Operation\OperationResource;
use App\Services\OperationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class OperationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected OperationService $operationService
    ) {}

    /**
     * Walk-in guest.
     *
     * Creates or reuses a guest, creates a reservation for the selected room,
     * and immediately checks the guest in.
     *
     * @group Operations(v1)
     *
     * @bodyParam first_name string required Guest first name. Example: John
     * @bodyParam last_name string required Guest last name. Example: Doe
     * @bodyParam id_number string required Guest identification number. Example: ID123456
     * @bodyParam phone_number string required Guest phone number. Example: 0712345678
     * @bodyParam email string Guest email address. Example: john@example.com
     * @bodyParam country string required Guest country. Example: Kenya
     * @bodyParam city string required Guest city. Example: Nairobi
     * @bodyParam address string Guest address. Example: 123 Main Street
     * @bodyParam room_id integer required ID of the available room. Example: 5
     * @bodyParam check_in date required Check-in date. Example: 2026-09-13
     * @bodyParam check_out date required Check-out date. Must be after check_in. Example: 2026-09-15
     * @bodyParam number_of_guests integer required Number of guests. Example: 2
     * @bodyParam notes string Additional notes about the walk-in. Example: Walk-in guest.
     *
     * @response 201 {
     *   "message": "Walk-in completed successfully.",
     *   "data": {
     *     "id": 1,
     *     "reservation_id": 10,
     *     "type": "check_in",
     *     "performed_at": "2026-09-13T10:30:00.000000Z",
     *     "notes": "Walk-in guest.",
     *     "performed_by": {
     *       "id": 5,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     },
     *     "created_at": "2026-09-13T10:30:00.000000Z"
     *   }
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     *
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "room_id": [
     *       "The selected room is not available."
     *     ]
     *   }
     * }
     */
    public function walkIn(WalkInRequest $request)
    {
        $this->authorize('create', \App\Models\Contract\Operation::class);

        $operation = $this->operationService->walkIn(
            guestData: [
                'first_name'   => $request->first_name,
                'last_name'    => $request->last_name,
                'id_number'    => $request->id_number,
                'phone_number' => $request->phone_number,
                'email'        => $request->email,
                'country'      => $request->country,
                'city'         => $request->city,
                'address'      => $request->address,
            ],
            roomId: $request->room_id,
            checkIn: $request->check_in,
            checkOut: $request->check_out,
            numberOfGuests: $request->number_of_guests,
            performedBy: $request->user()->id,
            notes: $request->notes
        );

        return ApiResponse::success(
            new OperationResource($operation),
            'Walk-in completed successfully.',
            201
        );
    }

    /**
     * Check in an existing reservation.
     *
     * Checks in a guest with an existing pending reservation.
     * The reservation must be pending and the associated room must
     * currently have a reserved status.
     *
     * @group Operations(v1)
     *
     * @bodyParam reservation_id integer required ID of the reservation to check in. Example: 10
     * @bodyParam notes string Additional notes about the check-in. Example: Guest arrived early.
     *
     * @response 200 {
     *   "message": "Guest checked in successfully.",
     *   "data": {
     *     "id": 1,
     *     "reservation_id": 10,
     *     "type": "check_in",
     *     "performed_at": "2026-09-13T10:30:00.000000Z",
     *     "notes": "Guest arrived early.",
     *     "performed_by": {
     *       "id": 5,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     },
     *     "created_at": "2026-09-13T10:30:00.000000Z"
     *   }
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     *
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "reservation": [
     *       "Only pending reservations can be checked in."
     *     ]
     *   }
     * }
     */
    public function checkIn(CheckInRequest $request)
    {
        $this->authorize('create', \App\Models\Contract\Operation::class);

        $operation = $this->operationService->checkIn(
            reservationId: $request->reservation_id,
            performedBy: $request->user()->id,
            notes: $request->notes
        );

        return ApiResponse::success(
            new OperationResource($operation),
            'Guest checked in successfully.'
        );
    }

    /**
     * Check out a guest.
     *
     * Checks out a guest with an existing checked-in reservation
     * and makes the occupied room available again.
     *
     * @group Operations(v1)
     *
     * @bodyParam reservation_id integer required ID of the reservation to check out. Example: 10
     * @bodyParam notes string Additional notes about the check-out. Example: Guest departed.
     *
     * @response 200 {
     *   "message": "Guest checked out successfully.",
     *   "data": {
     *     "id": 2,
     *     "reservation_id": 10,
     *     "type": "check_out",
     *     "performed_at": "2026-09-15T09:30:00.000000Z",
     *     "notes": "Guest departed.",
     *     "performed_by": {
     *       "id": 5,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     },
     *     "created_at": "2026-09-15T09:30:00.000000Z"
     *   }
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     *
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "reservation": [
     *       "Only checked-in reservations can be checked out."
     *     ]
     *   }
     * }
     */
    public function checkOut(CheckOutRequest $request)
    {
        $this->authorize('create', \App\Models\Contract\Operation::class);

        $operation = $this->operationService->checkOut(
            reservationId: $request->reservation_id,
            performedBy: $request->user()->id,
            notes: $request->notes
        );

        return ApiResponse::success(
            new OperationResource($operation),
            'Guest checked out successfully.'
        );
    }
}

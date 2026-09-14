<?php

namespace App\Http\Controllers\Api\V1\Folio;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Folio\CloseFolioRequest;
use App\Http\Requests\Api\V1\Folio\StoreFolioRequest;
use App\Http\Resources\Api\V1\Folio\FolioResource;
use App\Models\Folio\Folio;
use App\Models\Reservation\Reservation;
use App\Services\FolioService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class FolioController extends Controller
{
    use AuthorizesRequests;

    protected FolioService $folioService;

    public function __construct(FolioService $folioService)
    {
        $this->folioService = $folioService;
    }

    /**
     * Open a folio for a reservation
     *
     * @group Folios(v1)
     * @authenticated
     * @urlParam reservation integer required The ID of the reservation. Example: 1
     */
    public function store(
        StoreFolioRequest $request,
        Reservation $reservation
    ) {
        $this->authorize('create', Folio::class);

        $folio = $this->folioService->create([
            'reservation_id' => $reservation->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        return ApiResponse::success(
            new FolioResource($folio),
            'Folio opened successfully',
            201
        );
    }

    /**
     * Get the folio for a reservation
     *
     * @group Folios(v1)
     * @authenticated
     * @urlParam reservation integer required The ID of the reservation. Example: 1
     */
    public function byReservation(Reservation $reservation)
    {
        $folio = $this->folioService->getByReservation(
            $reservation->id
        );

        $this->authorize('view', $folio);

        return ApiResponse::success(
            new FolioResource($folio),
            'Folio retrieved successfully.'
        );
    }

    /**
     * Get a single folio
     *
     * @group Folios(v1)
     * @authenticated
     * @urlParam folio integer required The ID of the folio. Example: 1
     */
    public function show(Folio $folio)
    {
        $this->authorize('view', $folio);

        return ApiResponse::success(
            new FolioResource($folio),
            'Folio retrieved successfully.'
        );
    }

    /**
     * Close a folio
     *
     * @group Folios(v1)
     * @authenticated
     * @urlParam folio integer required The ID of the folio. Example: 1
     */
    public function close(
        CloseFolioRequest $request,
        Folio $folio
    ) {
        $this->authorize('update', $folio);

        $folio = $this->folioService->update($folio->id, [
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return ApiResponse::success(
            new FolioResource($folio),
            'Folio closed successfully.'
        );
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Guest\GuestIndexRequest;
use App\Http\Requests\Api\V1\Guest\GuestRequest;
use App\Http\Requests\Api\V1\Guest\UpdateGuestRequest;
use App\Http\Resources\Api\V1\Guest\GuestResource;
use App\Models\Guest\Guest;
use App\Services\GuestService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class GuestController extends Controller
{
    use AuthorizesRequests;

    protected GuestService $guestService;

    public function __construct(GuestService $guestService)
    {
        $this->guestService = $guestService;
    }

    /**
     * Get all guests list
     *
     * @group Guests(v1)
     * @authenticated
     * @queryParam filter[first_name] string Filter by first name. Example: John
     * @queryParam filter[last_name] string Filter by last name. Example: Doe
     * @queryParam filter[email] string Filter by email. Example: john@example.com
     * @queryParam filter[country] string Filter by country. Example: Kenya
     * @queryParam filter[city] string Filter by city. Example: Nairobi
     * @queryParam sort string Sort guests. Prefix with - for descending order. Example: -created_at
     * @queryParam per_page integer Number of guests per page. Example: 10
     */
    public function index(GuestIndexRequest $request)
    {
        $this->authorize('viewAny', Guest::class);

        $guests = $this->guestService->getAll();

        return ApiResponse::success(
            GuestResource::collection($guests),
            'Guests retrieved successfully',
            200,
            $guests
        );
    }

    /**
     * Create a guest
     *
     * @group Guests(v1)
     * @authenticated
     * @bodyParam first_name string required The guest's first name. Example: John
     * @bodyParam last_name string required The guest's last name. Example: Doe
     * @bodyParam phone_number string required The guest's phone number. Example: 0712345678
     * @bodyParam email string optional The guest's email address. Example: john@example.com
     * @bodyParam country string required The guest's country. Example: Kenya
     * @bodyParam city string required The guest's city. Example: Nairobi
     * @bodyParam address string optional The guest's address. Example: 123 Kenyatta Avenue
     */
    public function store(GuestRequest $request)
    {
        $this->authorize('create', Guest::class);

        $guest = $this->guestService->create(
            $request->validated()
        );

        return ApiResponse::success(
            new GuestResource($guest),
            'Guest created successfully',
            201
        );
    }

    /**
     * Get a single guest
     *
     * @group Guests(v1)
     * @authenticated
     * @urlParam id integer required The ID of the guest. Example: 1
     */
    public function show(string $id)
    {
        $guest = $this->guestService->findById((int) $id);

        $this->authorize('view', $guest);

        return ApiResponse::success(
            new GuestResource($guest),
            'Guest retrieved successfully'
        );
    }

    /**
     * Update a guest
     *
     * @group Guests(v1)
     * @authenticated
     * @urlParam id integer required The ID of the guest. Example: 1
     * @bodyParam first_name string The guest's first name. Example: John
     * @bodyParam last_name string The guest's last name. Example: Doe
     * @bodyParam phone_number string The guest's phone number. Example: 0712345678
     * @bodyParam email string The guest's email address. Example: john@example.com
     * @bodyParam country string The guest's country. Example: Kenya
     * @bodyParam city string The guest's city. Example: Nairobi
     * @bodyParam address string The guest's address. Example: 123 Kenyatta Avenue
     */
    public function update(UpdateGuestRequest $request, string $id)
    {
        $guest = $this->guestService->findById((int) $id);

        $this->authorize('update', $guest);

        $guest = $this->guestService->update(
            (int) $id,
            $request->validated()
        );

        return ApiResponse::success(
            new GuestResource($guest),
            'Guest updated successfully'
        );
    }

    /**
     * Delete a guest
     *
     * @group Guests(v1)
     * @authenticated
     * @urlParam id integer required The ID of the guest. Example: 1
     */
    public function destroy(string $id)
    {
        $guest = $this->guestService->findById((int) $id);

        $this->authorize('delete', $guest);

        $this->guestService->delete((int) $id);

        return ApiResponse::success(
            [],
            'Guest deleted successfully'
        );
    }
}

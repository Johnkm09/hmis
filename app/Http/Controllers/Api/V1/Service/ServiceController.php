<?php

namespace App\Http\Controllers\Api\V1\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Service\ServiceIndexRequest;
use App\Http\Requests\Api\V1\Service\ServiceRequest;
use App\Http\Requests\Api\V1\Service\UpdateServiceRequest;
use App\Http\Resources\Api\V1\Service\ServiceResource;
use App\Models\Service\Service;
use App\Services\ServiceService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ServiceController extends Controller
{
    use AuthorizesRequests;

    protected ServiceService $serviceService;

    public function __construct(ServiceService $serviceService)
    {
        $this->serviceService = $serviceService;
    }

    /**
     * Get all services list
     *
     * @group Services(v1)
     * @authenticated
     * @queryParam filter[name] string Filter by service name. Example: Laundry
     * @queryParam filter[is_active] boolean Filter by active status. Example: 1
     * @queryParam sort string Sort services. Prefix with - for descending. Example: -price
     * @queryParam per_page integer Number of services per page. Example: 10
     */
    public function index(ServiceIndexRequest $request)
    {
        $this->authorize('viewAny', Service::class);

        $services = $this->serviceService->getAll();

        return ApiResponse::success(
            ServiceResource::collection($services),
            'Services retrieved successfully.',
            200,
            $services
        );
    }

    /**
     * Create a service
     *
     * @group Services(v1)
     * @authenticated
     * @bodyParam name string required Service name. Example: Laundry
     * @bodyParam description string Service description. Example: Wash and fold clothes
     * @bodyParam price number required Service price. Example: 1500.00
     * @bodyParam is_active boolean Whether the service is active. Example: true
     */
    public function store(ServiceRequest $request)
    {
        $this->authorize('create', Service::class);

        $service = $this->serviceService->create(
            $request->validated()
        );

        return ApiResponse::success(
            new ServiceResource($service),
            'Service created successfully.',
            201
        );
    }

    /**
     * Get a single service
     *
     * @group Services(v1)
     * @authenticated
     * @urlParam id integer required The ID of the service. Example: 1
     */
    public function show(string $id)
    {
        $service = $this->serviceService->findById((int) $id);

        $this->authorize('view', $service);

        return ApiResponse::success(
            new ServiceResource($service),
            'Service retrieved successfully.'
        );
    }

    /**
     * Update a service
     *
     * @group Services(v1)
     * @authenticated
     * @urlParam id integer required The ID of the service. Example: 1
     * @bodyParam name string Service name. Example: Premium Laundry
     * @bodyParam description string Service description.
     * @bodyParam price number Service price. Example: 2000.00
     * @bodyParam is_active boolean Whether the service is active. Example: true
     */
    public function update(UpdateServiceRequest $request, string $id)
    {
        $service = $this->serviceService->findById((int) $id);

        $this->authorize('update', $service);

        $service = $this->serviceService->update(
            (int) $id,
            $request->validated()
        );

        return ApiResponse::success(
            new ServiceResource($service),
            'Service updated successfully.'
        );
    }

    /**
     * Delete a service
     *
     * @group Services(v1)
     * @authenticated
     * @urlParam id integer required The ID of the service. Example: 1
     */
    public function destroy(string $id)
    {
        $service = $this->serviceService->findById((int) $id);

        $this->authorize('delete', $service);

        $this->serviceService->delete((int) $id);

        return ApiResponse::success(
            [],
            'Service deleted successfully.'
        );
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\InvoiceIndexRequest;
use App\Http\Requests\Api\V1\Payment\StoreInvoiceRequest;
use App\Http\Requests\Api\V1\Payment\UpdateInvoiceRequest;
use App\Http\Resources\Api\V1\Payment\InvoiceResource;
use App\Models\Payment\Invoice;
use App\Services\InvoiceService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Get all invoices list
     *
     * @group Invoices(v1)
     * @authenticated
     * @queryParam filter[status] string Filter by invoice status. Example: issued
     * @queryParam filter[folio_id] integer Filter by folio ID. Example: 1
     * @queryParam filter[invoice_number] string Filter by invoice number. Example: 000001
     * @queryParam sort string Sort invoices. Prefix with - for descending order. Example: -created_at
     */
    public function index(InvoiceIndexRequest $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = $this->invoiceService->getAll();

        return ApiResponse::success(
            InvoiceResource::collection($invoices),
            'Invoices retrieved successfully',
            200,
            $invoices
        );
    }

    /**
     * Create an invoice
     *
     * @group Invoices(v1)
     * @authenticated
     * @bodyParam folio_id integer required The ID of the folio to invoice. Example: 1
     * @bodyParam tax_amount number optional The tax amount. Example: 3000
     * @bodyParam discount_amount number optional The discount amount. Example: 1000
     * @bodyParam status string required Invoice status. Example: issued
     * @bodyParam notes string optional Invoice notes. Example: Final guest invoice
     */
    public function store(StoreInvoiceRequest $request)
    {
        $this->authorize('create', Invoice::class);

        $invoice = $this->invoiceService->create(
            $request->validated()
        );

        return ApiResponse::success(
            new InvoiceResource($invoice),
            'Invoice created successfully',
            201
        );
    }

    /**
     * Get a single invoice
     *
     * @group Invoices(v1)
     * @authenticated
     * @urlParam id integer required The ID of the invoice. Example: 1
     */
    public function show(string $id)
    {
        $invoice = $this->invoiceService->findById((int) $id);

        $this->authorize('view', $invoice);

        return ApiResponse::success(
            new InvoiceResource($invoice),
            'Invoice retrieved successfully'
        );
    }

    /**
     * Update an invoice
     *
     * @group Invoices(v1)
     * @authenticated
     * @urlParam id integer required The ID of the invoice. Example: 1
     * @bodyParam tax_amount number The tax amount. Example: 3000
     * @bodyParam discount_amount number The discount amount. Example: 1000
     * @bodyParam status string The invoice status. Example: paid
     * @bodyParam notes string Invoice notes. Example: Invoice settled
     */
    public function update(UpdateInvoiceRequest $request, string $id)
    {
        $invoice = $this->invoiceService->findById((int) $id);

        $this->authorize('update', $invoice);

        $invoice = $this->invoiceService->update(
            (int) $id,
            $request->validated()
        );

        return ApiResponse::success(
            new InvoiceResource($invoice),
            'Invoice updated successfully'
        );
    }
}

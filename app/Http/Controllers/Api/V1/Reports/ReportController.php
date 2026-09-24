<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reports\ReportRequest;
use App\Services\ReportService;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Revenue Report
     *
     * Returns hotel revenue, collections, refunds and outstanding balance
     * for the selected period.
     *
     * @group Reports
     *
     * @authenticated
     *
     * @queryParam from date Optional start date. Example: 2026-09-01
     * @queryParam to date Optional end date. Example: 2026-09-30
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Revenue report generated successfully",
     *   "data": {
     *     "from": "2026-09-01",
     *     "to": "2026-09-30",
     *     "charges": 120000,
     *     "completed_payments": 95000,
     *     "completed_refunds": 5000,
     *     "net_collected": 90000,
     *     "outstanding": 25000
     *   }
     * }
     */
    public function revenue(ReportRequest $request)
    {
        $this->authorize('viewReports', \App\Models\User::class);

        $report = $this->reportService->revenue(
            $request->validated()
        );

        return ApiResponse::success(
            $report,
            'Revenue report generated successfully'
        );
    }

    /**
     * Occupancy Report
     *
     * Returns room occupancy and check-in/check-out statistics
     * for the selected period.
     *
     * @group Reports
     *
     * @authenticated
     *
     * @queryParam from date Optional start date. Example: 2026-09-01
     * @queryParam to date Optional end date. Example: 2026-09-30
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Occupancy report generated successfully",
     *   "data": {
     *     "from": "2026-09-01",
     *     "to": "2026-09-30",
     *     "occupied_room_nights": 45,
     *     "available_room_nights": 150,
     *     "occupancy_percentage": 30,
     *     "check_ins": 12,
     *     "check_outs": 10
     *   }
     * }
     */
    public function occupancy(ReportRequest $request)
    {
        $this->authorize('viewReports', \App\Models\User::class);

        $report = $this->reportService->occupancy(
            $request->validated()
        );

        return ApiResponse::success(
            $report,
            'Occupancy report generated successfully'
        );
    }

    /**
     * Reservations Report
     *
     * Returns reservation totals grouped by reservation status
     * for the selected period.
     *
     * @group Reports
     *
     * @authenticated
     *
     * @queryParam from date Optional start date. Example: 2026-09-01
     * @queryParam to date Optional end date. Example: 2026-09-30
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Reservations report generated successfully",
     *   "data": {
     *     "from": "2026-09-01",
     *     "to": "2026-09-30",
     *     "total": 40,
     *     "pending": 5,
     *     "confirmed": 15,
     *     "checked_in": 10,
     *     "checked_out": 10
     *   }
     * }
     */
    public function reservations(ReportRequest $request)
    {
        $this->authorize('viewReports', \App\Models\User::class);

        $report = $this->reportService->reservations(
            $request->validated()
        );

        return ApiResponse::success(
            $report,
            'Reservations report generated successfully'
        );
    }

    /**
     * Payments Report
     *
     * Returns payment counts, completed payment totals and
     * completed amounts grouped by payment method.
     *
     * @group Reports
     *
     * @authenticated
     *
     * @queryParam from date Optional start date. Example: 2026-09-01
     * @queryParam to date Optional end date. Example: 2026-09-30
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Payments report generated successfully",
     *   "data": {
     *     "from": "2026-09-01",
     *     "to": "2026-09-30",
     *     "total": 30,
     *     "completed": 20,
     *     "pending": 5,
     *     "failed": 5,
     *     "total_completed_amount": 150000,
     *     "mpesa": 80000,
     *     "stripe": 50000,
     *     "cash": 20000
     *   }
     * }
     */
    public function payments(ReportRequest $request)
    {
        $this->authorize('viewReports', \App\Models\User::class);

        $report = $this->reportService->payments(
            $request->validated()
        );

        return ApiResponse::success(
            $report,
            'Payments report generated successfully'
        );
    }
}

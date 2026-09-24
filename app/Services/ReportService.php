<?php

namespace App\Services;

use App\Models\Folio\FolioCharge;
use App\Models\Payment\Payment;
use App\Models\Payment\Refund;
use Carbon\Carbon;

class ReportService
{
    public function revenue(array $filters)
    {
        [$from, $to] = $this->resolveDateRange($filters);

        $charges = FolioCharge::query()
            ->whereBetween('charged_at', [$from, $to])
            ->sum('amount');

        $completedPayments = Payment::query()
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        $completedRefunds = Refund::query()
            ->where('status', 'completed')
            ->whereBetween('refunded_at', [$from, $to])
            ->sum('amount');

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'charges' => (float) $charges,
            'completed_payments' => (float) $completedPayments,
            'completed_refunds' => (float) $completedRefunds,
            'net_collected' => (float) ($completedPayments - $completedRefunds),
            'outstanding' => (float) ($charges - $completedPayments),
        ];
    }

    public function occupancy(array $filters)
    {
        [$from, $to] = $this->resolveDateRange($filters);

        $totalDays = $from->diffInDays($to) + 1;

        $occupiedRoomNights = \App\Models\Reservation\Reservation::query()
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->whereDate('check_in', '<=', $to)
            ->whereDate('check_out', '>', $from)
            ->get()
            ->sum(function ($reservation) use ($from, $to) {
                $start = $reservation->check_in->greaterThan($from)
                    ? $reservation->check_in
                    : $from;

                $end = $reservation->check_out->lessThan($to)
                    ? $reservation->check_out
                    : $to;

                return (int) $start->diffInDays($end);
            });

        $totalRooms = \App\Models\Room\Room::query()->count();

        $availableRoomNights = (int) ($totalRooms * $totalDays);

        $occupancyPercentage = $availableRoomNights > 0
            ? ($occupiedRoomNights / $availableRoomNights) * 100
            : 0;

        $checkIns = \App\Models\Reservation\Reservation::query()
            ->where('status', 'checked_in')
            ->whereBetween('check_in', [$from, $to])
            ->count();

        $checkOuts = \App\Models\Reservation\Reservation::query()
            ->where('status', 'checked_out')
            ->whereBetween('check_out', [$from, $to])
            ->count();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'occupied_room_nights' => $occupiedRoomNights,
            'available_room_nights' => $availableRoomNights,
            'occupancy_percentage' => round($occupancyPercentage, 2),
            'check_ins' => $checkIns,
            'check_outs' => $checkOuts,
        ];
    }

    public function reservations(array $filters)
    {
        [$from, $to] = $this->resolveDateRange($filters);

        $query = \App\Models\Reservation\Reservation::query()
            ->whereBetween('created_at', [$from, $to]);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'confirmed' => (clone $query)->where('status', 'confirmed')->count(),
            'checked_in' => (clone $query)->where('status', 'checked_in')->count(),
            'checked_out' => (clone $query)->where('status', 'checked_out')->count(),
        ];
    }

    public function payments(array $filters)
    {
        [$from, $to] = $this->resolveDateRange($filters);

        $query = \App\Models\Payment\Payment::query()
            ->whereBetween('created_at', [$from, $to]);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'total' => (clone $query)->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'total_completed_amount' => (float) (clone $query)
                ->where('status', 'completed')
                ->sum('amount'),
            'mpesa' => (float) (clone $query)
                ->where('status', 'completed')
                ->where('method', 'mpesa')
                ->sum('amount'),
            'stripe' => (float) (clone $query)
                ->where('status', 'completed')
                ->where('method', 'stripe')
                ->sum('amount'),
            'cash' => (float) (clone $query)
                ->where('status', 'completed')
                ->where('method', 'cash')
                ->sum('amount'),
        ];
    }

    private function resolveDateRange(array $filters)
    {
        $from = isset($filters['from'])
            ? Carbon::parse($filters['from'])->startOfDay()
            : now()->startOfMonth();

        $to = isset($filters['to'])
            ? Carbon::parse($filters['to'])->endOfDay()
            : now()->endOfMonth();

        return [$from, $to];
    }
}

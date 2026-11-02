<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Supply;
use App\Models\SupplySchedule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class DailyReportController extends Controller
{
    /**
     * Download the end-of-day bottle booking sheet as a PDF.
     */
    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', Booking::class);

        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::parse($validated['date'] ?? today());

        $pendingSupplies = Customer::query()
            ->withPendingBalance()
            ->orderByDesc('balance')
            ->get();

        $todaysSupplies = Customer::query()
            ->withBookingSummary()
            ->whereHas('supplies', fn ($query) => $query->whereDate('date', $date))
            ->withSum(['supplies as qty_supplied_today' => fn ($query) => $query->whereDate('date', $date)], 'qty')
            ->orderBy('name')
            ->get();

        $proposedSchedule = SupplySchedule::query()
            ->whereDate('date', '>', $date)
            ->orderBy('date')
            ->with(['items.customer' => fn ($query) => $query->withBookingTotals()])
            ->first();

        $pdf = Pdf::loadView('reports.daily-booking-sheet', [
            'date' => $date,
            'generatedAt' => now(),
            'generatedBy' => $request->user(),
            'pendingSupplies' => $pendingSupplies,
            'proposedSchedule' => $proposedSchedule,
            'todaysSupplies' => $todaysSupplies,
            'todaysSupplyTotal' => Supply::query()->whereDate('date', $date)->sum('qty'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download("bottle_booking_sheet_{$date->toDateString()}.pdf");
    }
}

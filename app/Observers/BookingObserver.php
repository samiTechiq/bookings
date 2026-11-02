<?php

namespace App\Observers;

use App\Enums\LedgerType;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;

class BookingObserver
{
    /**
     * Attribute the booking to the authenticated user when not set explicitly.
     */
    public function creating(Booking $booking): void
    {
        $booking->user_id ??= Auth::id();
        $booking->amount ??= 0;
    }

    /**
     * Record the booking as an `in` movement on the customer's ledger.
     */
    public function created(Booking $booking): void
    {
        $booking->ledger()->create([
            'customer_id' => $booking->customer_id,
            'type' => LedgerType::In,
            'qty' => $booking->qty,
            'date' => $booking->date,
        ]);
    }

    /**
     * Keep the ledger entry in sync when the booking is corrected.
     */
    public function updated(Booking $booking): void
    {
        $booking->ledger()->updateOrCreate([], [
            'customer_id' => $booking->customer_id,
            'type' => LedgerType::In,
            'qty' => $booking->qty,
            'date' => $booking->date,
        ]);
    }
}

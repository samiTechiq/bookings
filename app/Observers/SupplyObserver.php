<?php

namespace App\Observers;

use App\Enums\LedgerType;
use App\Models\Supply;
use Illuminate\Support\Facades\Auth;

class SupplyObserver
{
    /**
     * Attribute the supply to the authenticated user when not set explicitly.
     */
    public function creating(Supply $supply): void
    {
        $supply->user_id ??= Auth::id();
    }

    /**
     * Record the supply as an `out` movement on the customer's ledger.
     */
    public function created(Supply $supply): void
    {
        $supply->ledger()->create([
            'customer_id' => $supply->customer_id,
            'type' => LedgerType::Out,
            'qty' => $supply->qty,
            'date' => $supply->date,
        ]);
    }

    /**
     * Keep the ledger entry in sync when the supply is corrected.
     */
    public function updated(Supply $supply): void
    {
        $supply->ledger()->updateOrCreate([], [
            'customer_id' => $supply->customer_id,
            'type' => LedgerType::Out,
            'qty' => $supply->qty,
            'date' => $supply->date,
        ]);
    }
}

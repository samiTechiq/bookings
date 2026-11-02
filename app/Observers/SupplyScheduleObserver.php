<?php

namespace App\Observers;

use App\Models\SupplySchedule;
use Illuminate\Support\Facades\Auth;

class SupplyScheduleObserver
{
    /**
     * Attribute the schedule to the authenticated user when not set explicitly.
     */
    public function creating(SupplySchedule $supplySchedule): void
    {
        $supplySchedule->user_id ??= Auth::id();
    }
}

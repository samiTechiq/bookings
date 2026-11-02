<?php

namespace App\Models;

use Database\Factories\SupplyScheduleItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['supply_schedule_id', 'customer_id', 'qty'])]
class SupplyScheduleItem extends Model
{
    /** @use HasFactory<SupplyScheduleItemFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SupplySchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(SupplySchedule::class, 'supply_schedule_id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

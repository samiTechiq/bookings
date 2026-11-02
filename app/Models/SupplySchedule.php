<?php

namespace App\Models;

use App\Observers\SupplyScheduleObserver;
use Database\Factories\SupplyScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Proposed supplies for a given day, prepared by the manager at close of business.
 */
#[Fillable(['date', 'user_id'])]
#[ObservedBy(SupplyScheduleObserver::class)]
class SupplySchedule extends Model
{
    /** @use HasFactory<SupplyScheduleFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @return HasMany<SupplyScheduleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SupplyScheduleItem::class);
    }

    /**
     * The user (e.g. Marketing Manager) who prepared this schedule.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

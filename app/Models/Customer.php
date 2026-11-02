<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'address', 'phone'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, HasUuids;

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<Supply, $this>
     */
    public function supplies(): HasMany
    {
        return $this->hasMany(Supply::class);
    }

    /**
     * @return HasMany<Ledger, $this>
     */
    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class);
    }

    public const QTY_BOOKED_SQL = '(SELECT COALESCE(SUM(qty), 0) FROM requests WHERE requests.customer_id = customers.id)';

    public const AMOUNT_PAID_SQL = '(SELECT COALESCE(SUM(amount), 0) FROM requests WHERE requests.customer_id = customers.id)';

    public const QTY_SUPPLIED_SQL = '(SELECT COALESCE(SUM(qty), 0) FROM supplies WHERE supplies.customer_id = customers.id)';

    public const LATEST_DATE_SQL = '(SELECT MAX(date) FROM ledgers WHERE ledgers.customer_id = customers.id)';

    public const BALANCE_SQL = '('.self::QTY_BOOKED_SQL.' - '.self::QTY_SUPPLIED_SQL.')';

    /**
     * Add booking/supply aggregates (qty_booked, amount_paid, qty_supplied,
     * balance, latest_date) to the query.
     *
     * @param  Builder<Customer>  $query
     */
    public function scopeWithBookingTotals(Builder $query): void
    {
        $query
            ->select('customers.*')
            ->selectRaw(self::QTY_BOOKED_SQL.' as qty_booked')
            ->selectRaw(self::AMOUNT_PAID_SQL.' as amount_paid')
            ->selectRaw(self::QTY_SUPPLIED_SQL.' as qty_supplied')
            ->selectRaw(self::BALANCE_SQL.' as balance')
            ->selectRaw(self::LATEST_DATE_SQL.' as latest_date');
    }

    /**
     * Add booking/supply aggregates and limit the query to customers with activity.
     *
     * @param  Builder<Customer>  $query
     */
    public function scopeWithBookingSummary(Builder $query): void
    {
        $query
            ->withBookingTotals()
            ->where(fn (Builder $query) => $query->whereHas('bookings')->orWhereHas('supplies'));
    }

    /**
     * Limit the summary query to customers with an outstanding (unsupplied) balance.
     *
     * @param  Builder<Customer>  $query
     */
    public function scopeWithPendingBalance(Builder $query): void
    {
        $query->withBookingSummary()->whereRaw(self::BALANCE_SQL.' > 0');
    }
}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>bottle_booking_sheet_{{ $date->toDateString() }}</title>
    <style>
        @page { margin: 28px 32px 48px; }

        * { box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #1f2937;
            margin: 0;
        }

        .header {
            background: #0c4a6e;
            color: #ffffff;
            padding: 18px 20px 14px;
            border-radius: 6px;
        }

        .header table { width: 100%; border-collapse: collapse; }

        .company {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 0;
        }

        .subtitle {
            font-size: 11px;
            color: #bae6fd;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .header .meta {
            text-align: right;
            font-size: 10px;
            color: #e0f2fe;
            line-height: 1.6;
        }

        .header .meta strong { color: #ffffff; font-size: 13px; }

        .summary { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 16px -8px 4px; }

        .summary td {
            width: 33.33%;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            padding: 10px 12px;
        }

        .summary .label { font-size: 9px; color: #0369a1; text-transform: uppercase; letter-spacing: 1px; }
        .summary .value { font-size: 18px; font-weight: bold; color: #0c4a6e; margin-top: 2px; }

        h2 {
            font-size: 13px;
            color: #0c4a6e;
            margin: 22px 0 8px;
            padding-bottom: 5px;
            border-bottom: 2px solid #0ea5e9;
        }

        h2 .count { font-size: 10px; color: #6b7280; font-weight: normal; }

        table.data { width: 100%; border-collapse: collapse; }

        table.data th {
            background: #e0f2fe;
            color: #0c4a6e;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            padding: 7px 8px;
            border-bottom: 1px solid #7dd3fc;
        }

        table.data td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f9fafb; }
        table.data tfoot td { font-weight: bold; background: #f0f9ff; border-top: 2px solid #0ea5e9; border-bottom: none; }

        .num { text-align: right; white-space: nowrap; }
        .muted { color: #6b7280; font-size: 9px; }
        .balance { font-weight: bold; color: #b45309; }
        .supplied { font-weight: bold; color: #047857; }
        .proposed { font-weight: bold; color: #0369a1; }

        .empty {
            padding: 14px;
            text-align: center;
            color: #6b7280;
            background: #f9fafb;
            border: 1px dashed #d1d5db;
            border-radius: 6px;
        }

        .signatures { width: 100%; margin-top: 40px; border-collapse: collapse; }
        .signatures td { width: 50%; padding-top: 30px; }
        .signatures .line { border-top: 1px solid #9ca3af; width: 80%; padding-top: 4px; font-size: 9px; color: #6b7280; }

        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            font-size: 8.5px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="footer">
        NATU FOOD AND BEVERAGE LIMITED &middot; Bottle Booking Sheet &middot; Generated {{ $generatedAt->format('d M Y, g:i A') }}@if ($generatedBy) by {{ $generatedBy->name }}@endif
    </div>

    <div class="header">
        <table>
            <tr>
                <td>
                    <p class="company">NATU FOOD AND BEVERAGE LIMITED</p>
                    <div class="subtitle">Bottle Booking Sheet &mdash; End of Day Report</div>
                </td>
                <td class="meta">
                    <strong>{{ $date->format('l, d F Y') }}</strong><br>
                    Report date
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="label">Customers Pending Supply</div>
                <div class="value">{{ number_format($pendingSupplies->count()) }}</div>
            </td>
            <td>
                <div class="label">Total Bottles Outstanding</div>
                <div class="value">{{ number_format($pendingSupplies->sum('balance')) }}</div>
            </td>
            <td>
                <div class="label">Bottles Supplied Today</div>
                <div class="value">{{ number_format($todaysSupplyTotal) }}</div>
            </td>
        </tr>
    </table>

    <h2>1. Pending Supplies <span class="count">&mdash; bookings with an outstanding balance</span></h2>

    @if ($pendingSupplies->isEmpty())
        <div class="empty">All bookings have been fully supplied. Nothing pending.</div>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 4%">#</th>
                    <th>Customer</th>
                    <th style="width: 13%">Last Activity</th>
                    <th class="num" style="width: 11%">Booked</th>
                    <th class="num" style="width: 15%">Amount Paid</th>
                    <th class="num" style="width: 11%">Supplied</th>
                    <th class="num" style="width: 11%">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pendingSupplies as $customer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $customer->name }}</strong>
                            @if ($customer->phone || $customer->address)
                                <div class="muted">{{ collect([$customer->phone, $customer->address])->filter()->implode(' · ') }}</div>
                            @endif
                        </td>
                        <td>{{ $customer->latest_date ? \Illuminate\Support\Carbon::parse($customer->latest_date)->format('d M Y') : '—' }}</td>
                        <td class="num">{{ number_format($customer->qty_booked) }}</td>
                        <td class="num">&#8358;{{ number_format($customer->amount_paid, 2) }}</td>
                        <td class="num">{{ number_format($customer->qty_supplied) }}</td>
                        <td class="num balance">{{ number_format($customer->balance) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Total</td>
                    <td class="num">{{ number_format($pendingSupplies->sum('qty_booked')) }}</td>
                    <td class="num">&#8358;{{ number_format($pendingSupplies->sum('amount_paid'), 2) }}</td>
                    <td class="num">{{ number_format($pendingSupplies->sum('qty_supplied')) }}</td>
                    <td class="num">{{ number_format($pendingSupplies->sum('balance')) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <h2>2. Proposed Supply Schedule <span class="count">&mdash; {{ $proposedSchedule ? $proposedSchedule->date->format('l, d M Y') : 'next supply day' }}</span></h2>

    @if (! $proposedSchedule || $proposedSchedule->items->isEmpty())
        <div class="empty">No supply schedule has been prepared for the next supply day.</div>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 4%">#</th>
                    <th>Customer</th>
                    <th style="width: 18%">Phone</th>
                    <th class="num" style="width: 16%">Current Balance</th>
                    <th class="num" style="width: 16%">Proposed Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($proposedSchedule->items->sortByDesc('qty') as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $item->customer->name }}</strong>
                            @if ($item->customer->address)
                                <div class="muted">{{ $item->customer->address }}</div>
                            @endif
                        </td>
                        <td>{{ $item->customer->phone ?: '—' }}</td>
                        <td class="num">{{ number_format($item->customer->balance) }}</td>
                        <td class="num proposed">{{ number_format($item->qty) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Total</td>
                    <td class="num">{{ number_format($proposedSchedule->items->sum(fn ($item) => $item->customer->balance)) }}</td>
                    <td class="num">{{ number_format($proposedSchedule->items->sum('qty')) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <h2>3. Today's Supplies <span class="count">&mdash; {{ $date->format('d M Y') }}</span></h2>

    @if ($todaysSupplies->isEmpty())
        <div class="empty">No supplies were recorded for this date.</div>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 4%">#</th>
                    <th>Customer</th>
                    <th style="width: 18%">Phone</th>
                    <th class="num" style="width: 16%">Qty Supplied Today</th>
                    <th class="num" style="width: 16%">Remaining Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($todaysSupplies as $customer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $customer->name }}</strong></td>
                        <td>{{ $customer->phone ?: '—' }}</td>
                        <td class="num supplied">{{ number_format($customer->qty_supplied_today) }}</td>
                        <td class="num">{{ number_format($customer->balance) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Total</td>
                    <td class="num">{{ number_format($todaysSupplyTotal) }}</td>
                    <td class="num">{{ number_format($todaysSupplies->sum('balance')) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <table class="signatures">
        <tr>
            <td><div class="line">Prepared by (Marketing Manager)</div></td>
            <td><div class="line">Approved by</div></td>
        </tr>
    </table>
</body>
</html>

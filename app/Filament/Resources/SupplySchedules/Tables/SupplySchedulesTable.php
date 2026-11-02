<?php

namespace App\Filament\Resources\SupplySchedules\Tables;

use App\Models\SupplySchedule;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SupplySchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('user')
                ->withCount('items')
                ->withSum('items', 'qty'))
            ->columns([
                TextColumn::make('date')
                    ->label('Supply Date')
                    ->date('D, d M Y')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (SupplySchedule $record): ?string => match (true) {
                        $record->date->isToday() => 'Today',
                        $record->date->isTomorrow() => 'Tomorrow',
                        default => null,
                    }),
                TextColumn::make('items_count')
                    ->label('Customers')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('items_sum_qty')
                    ->label('Proposed Qty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Prepared By')
                    ->placeholder('—')
                    ->visible(fn (): bool => (bool) Auth::user()?->isRootAdmin()),
            ])
            ->defaultSort('date', 'desc')
            ->stackedOnMobile()
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\SupplySchedules;

use App\Filament\Resources\SupplySchedules\Pages\CreateSupplySchedule;
use App\Filament\Resources\SupplySchedules\Pages\EditSupplySchedule;
use App\Filament\Resources\SupplySchedules\Pages\ListSupplySchedules;
use App\Filament\Resources\SupplySchedules\Schemas\SupplyScheduleForm;
use App\Filament\Resources\SupplySchedules\Tables\SupplySchedulesTable;
use App\Models\SupplySchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SupplyScheduleResource extends Resource
{
    protected static ?string $model = SupplySchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Business';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'supply schedule';

    public static function form(Schema $schema): Schema
    {
        return SupplyScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplySchedulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupplySchedules::route('/'),
            'create' => CreateSupplySchedule::route('/create'),
            'edit' => EditSupplySchedule::route('/{record}/edit'),
        ];
    }
}

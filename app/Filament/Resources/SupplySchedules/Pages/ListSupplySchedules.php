<?php

namespace App\Filament\Resources\SupplySchedules\Pages;

use App\Filament\Resources\SupplySchedules\SupplyScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplySchedules extends ListRecords
{
    protected static string $resource = SupplyScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\SupplySchedules\Pages;

use App\Filament\Resources\SupplySchedules\SupplyScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplySchedule extends EditRecord
{
    protected static string $resource = SupplyScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

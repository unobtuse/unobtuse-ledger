<?php

namespace App\Filament\Resources\PaySchedules\Pages;

use App\Filament\Resources\PaySchedules\PayScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaySchedules extends ListRecords
{
    protected static string $resource = PayScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\PaySchedules\Pages;

use App\Filament\Resources\PaySchedules\PayScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPaySchedule extends EditRecord
{
    protected static string $resource = PayScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

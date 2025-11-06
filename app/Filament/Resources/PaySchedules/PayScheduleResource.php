<?php

namespace App\Filament\Resources\PaySchedules;

use App\Filament\Resources\PaySchedules\Pages\CreatePaySchedule;
use App\Filament\Resources\PaySchedules\Pages\EditPaySchedule;
use App\Filament\Resources\PaySchedules\Pages\ListPaySchedules;
use App\Filament\Resources\PaySchedules\Schemas\PayScheduleForm;
use App\Filament\Resources\PaySchedules\Tables\PaySchedulesTable;
use App\Models\PaySchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PayScheduleResource extends Resource
{
    protected static ?string $model = PaySchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PayScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaySchedulesTable::configure($table);
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
            'index' => ListPaySchedules::route('/'),
            'create' => CreatePaySchedule::route('/create'),
            'edit' => EditPaySchedule::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

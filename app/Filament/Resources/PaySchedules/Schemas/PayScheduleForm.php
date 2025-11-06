<?php

namespace App\Filament\Resources\PaySchedules\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PayScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('frequency')
                    ->required()
                    ->default('biweekly'),
                TextInput::make('pay_day_of_week'),
                TextInput::make('pay_day_of_month_1')
                    ->numeric(),
                TextInput::make('pay_day_of_month_2')
                    ->numeric(),
                TextInput::make('custom_schedule'),
                DatePicker::make('next_pay_date')
                    ->required(),
                TextInput::make('gross_pay')
                    ->numeric(),
                TextInput::make('net_pay')
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->default('USD'),
                TextInput::make('employer_name'),
                Toggle::make('is_active')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('metadata'),
            ]);
    }
}

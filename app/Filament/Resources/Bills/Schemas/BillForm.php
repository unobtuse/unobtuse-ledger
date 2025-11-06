<?php

namespace App\Filament\Resources\Bills\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('account_id')
                    ->relationship('account', 'id'),
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->default('USD'),
                DatePicker::make('due_date')
                    ->required(),
                DatePicker::make('next_due_date')
                    ->required(),
                TextInput::make('frequency')
                    ->required()
                    ->default('monthly'),
                TextInput::make('frequency_value')
                    ->numeric(),
                TextInput::make('category')
                    ->required()
                    ->default('other'),
                TextInput::make('payment_status')
                    ->required()
                    ->default('upcoming'),
                DatePicker::make('last_payment_date'),
                TextInput::make('last_payment_amount')
                    ->numeric(),
                Toggle::make('is_autopay')
                    ->required(),
                TextInput::make('autopay_account'),
                TextInput::make('payment_link'),
                TextInput::make('payee_name'),
                Toggle::make('auto_detected')
                    ->required(),
                TextInput::make('source_transaction_id'),
                TextInput::make('detection_confidence')
                    ->numeric(),
                Toggle::make('reminder_enabled')
                    ->required(),
                TextInput::make('reminder_days_before')
                    ->required()
                    ->numeric()
                    ->default(3),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('priority')
                    ->required()
                    ->default('medium'),
                TextInput::make('metadata'),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('account_id')
                    ->relationship('account', 'id')
                    ->required(),
                TextInput::make('plaid_transaction_id'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('merchant_name'),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('iso_currency_code')
                    ->required()
                    ->default('USD'),
                DatePicker::make('transaction_date')
                    ->required(),
                DatePicker::make('authorized_date'),
                DatePicker::make('posted_date'),
                TextInput::make('category'),
                TextInput::make('plaid_categories'),
                TextInput::make('category_id'),
                TextInput::make('category_confidence')
                    ->numeric(),
                TextInput::make('transaction_type')
                    ->required()
                    ->default('debit'),
                Toggle::make('pending')
                    ->required(),
                Toggle::make('is_recurring')
                    ->required(),
                TextInput::make('recurring_frequency'),
                TextInput::make('recurring_group_id'),
                TextInput::make('location_address'),
                TextInput::make('location_city'),
                TextInput::make('location_region'),
                TextInput::make('location_postal_code'),
                TextInput::make('location_country'),
                TextInput::make('location_lat')
                    ->numeric(),
                TextInput::make('location_lon')
                    ->numeric(),
                TextInput::make('payment_channel'),
                TextInput::make('user_category'),
                Textarea::make('user_notes')
                    ->columnSpanFull(),
                TextInput::make('tags'),
                TextInput::make('metadata'),
            ]);
    }
}

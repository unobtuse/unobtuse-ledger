<?php

namespace App\Filament\Resources\Accounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('plaid_account_id')
                    ->required(),
                TextInput::make('plaid_item_id')
                    ->required(),
                TextInput::make('account_name')
                    ->required(),
                TextInput::make('official_name'),
                TextInput::make('account_type')
                    ->required(),
                TextInput::make('account_subtype'),
                TextInput::make('institution_id'),
                TextInput::make('institution_name')
                    ->required(),
                TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('available_balance')
                    ->numeric(),
                TextInput::make('credit_limit')
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->default('USD'),
                TextInput::make('mask'),
                TextInput::make('account_number'),
                TextInput::make('routing_number'),
                TextInput::make('sync_status')
                    ->required()
                    ->default('synced'),
                DateTimePicker::make('last_synced_at'),
                Textarea::make('sync_error')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('metadata'),
            ]);
    }
}

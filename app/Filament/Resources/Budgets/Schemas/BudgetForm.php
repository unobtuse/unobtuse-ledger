<?php

namespace App\Filament\Resources\Budgets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BudgetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('month')
                    ->required(),
                TextInput::make('total_income')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('expected_income')
                    ->numeric(),
                TextInput::make('bills_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('bills_paid')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('bills_pending')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('spending_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('spending_by_category')
                    ->numeric(),
                TextInput::make('category_breakdown'),
                TextInput::make('rent_allocation')
                    ->numeric(),
                Toggle::make('rent_paid')
                    ->required(),
                TextInput::make('remaining_budget')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('available_to_spend')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('savings_goal')
                    ->numeric(),
                TextInput::make('savings_actual')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('spending_limit')
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                TextInput::make('transactions_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('bills_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('metadata'),
            ]);
    }
}

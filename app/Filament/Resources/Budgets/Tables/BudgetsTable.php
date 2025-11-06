<?php

namespace App\Filament\Resources\Budgets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BudgetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('month')
                    ->searchable(),
                TextColumn::make('total_income')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('expected_income')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bills_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bills_paid')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bills_pending')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('spending_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('spending_by_category')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rent_allocation')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('rent_paid')
                    ->boolean(),
                TextColumn::make('remaining_budget')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('available_to_spend')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('savings_goal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('savings_actual')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('spending_limit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('transactions_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bills_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}

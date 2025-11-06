<?php

namespace App\Filament\Resources\Transactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('account.id')
                    ->searchable(),
                TextColumn::make('plaid_transaction_id')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('merchant_name')
                    ->searchable(),
                TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('iso_currency_code')
                    ->searchable(),
                TextColumn::make('transaction_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('authorized_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('posted_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('category')
                    ->searchable(),
                TextColumn::make('category_id')
                    ->searchable(),
                TextColumn::make('category_confidence')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('transaction_type')
                    ->searchable(),
                IconColumn::make('pending')
                    ->boolean(),
                IconColumn::make('is_recurring')
                    ->boolean(),
                TextColumn::make('recurring_frequency')
                    ->searchable(),
                TextColumn::make('recurring_group_id'),
                TextColumn::make('location_address')
                    ->searchable(),
                TextColumn::make('location_city')
                    ->searchable(),
                TextColumn::make('location_region')
                    ->searchable(),
                TextColumn::make('location_postal_code')
                    ->searchable(),
                TextColumn::make('location_country')
                    ->searchable(),
                TextColumn::make('location_lat')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('location_lon')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('payment_channel')
                    ->searchable(),
                TextColumn::make('user_category')
                    ->searchable(),
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

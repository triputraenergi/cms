<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestTransactions extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->orderBy('booking_date_time', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('account.institution_code')
                    ->label('Institution Code'),
                Tables\Columns\TextColumn::make('account_identification')
                    ->label('Account Number'),
                Tables\Columns\TextColumn::make('transaction_information')
                    ->label('Description'),

                Tables\Columns\TextColumn::make('transaction_amount')
                    ->label('Amount')
                    ->money(fn (Transaction $record): string => $record->transaction_currency)
                    ->sortable(),

                Tables\Columns\TextColumn::make('booking_date_time')
                    ->label('Date')
                    ->date(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account_identification')
                    ->label('Filter by Account')
                    ->options(
                        Transaction::query()
                            ->distinct()
                            ->pluck('account_identification', 'account_identification')
                            ->all()
                    )
            ])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([5, 10, 25, 50, 'all']);
    }
}

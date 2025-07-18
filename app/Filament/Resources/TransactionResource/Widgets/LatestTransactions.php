<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use http\Client\Curl\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class LatestTransactions extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public ?array $filters = [];

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $accountNumber = $this->filters['account_number'] ?? null;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;
        $creditDebitIndicator = $this->filters['credit_debit_indicator'] ?? null;

        return $table
            ->query(Transaction::query()
//                ->with(['account'])
                ->when($accountNumber, fn ($query, $account) =>
                    $query->where('account_identification', $account)
                )->when($startDate, fn ($query, $startDate) =>
                $query->whereDate('value_date_time', '>=', $startDate)
                )->when($endDate, fn ($query, $endDate) =>
                $query->whereDate('value_date_time', '<=', $endDate)
                )->when($creditDebitIndicator, fn ($query, $creditDebitIndicator) =>
                $query->where('credit_debit_indicator', $creditDebitIndicator)
                )->whereHas('account.company', fn ($query) =>
                $query->where('code', $user['company_code'])
                )
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
                Tables\Columns\TextColumn::make('credit_debit_indicator')
                    ->label('Credit/Debit')
                    ->badge()
                    // This transforms the raw 'C' or 'D' value into a readable string
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'C' => 'Credit',
                        'D' => 'Debit',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'C' => 'success', // Green for Credit
                        'D' => 'danger',  // Red for Debit
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('booking_date_time')
                    ->label('Date')
                    ->date(),
            ])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([5, 10, 25, 50, 'all']);
    }

    // Listen to the Livewire event and update the filters
    #[On('updateWidgetData')]
    public function updateData($data)
    {
        $this->filters = $data;
        $this->resetTable();
    }
}

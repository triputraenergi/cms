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
        return $table

            ->query(function () {
                // Get the current, up-to-date filter values
                $user = Auth::user();
                $institutionCode = $this->filters['institution_code'] ?? null;
                $accountNumber = $this->filters['account_number'] ?? null;
                $startDate = $this->filters['start_date'] ?? null;
                $endDate = $this->filters['end_date'] ?? null;
                $creditDebitIndicator = $this->filters['credit_debit_indicator'] ?? null;

                // Start the base query
                $query = Transaction::query();

                // Apply Account Number filter
                $query->when($accountNumber, function ($q, $account) {
                    return $q->where('account_identification', $account);
                });

                // Apply Institution Code filter
                $query->when($institutionCode, function ($q, $code) {
                    $cleanCode = trim($code);
                    // This log should now print correctly whenever an institution is selected
                    return $q->whereHas('account', function ($subQuery) use ($cleanCode) {
                        $subQuery->where('institution_code', $cleanCode);
                    });
                });

                // Apply Start Date filter
                $query->when($startDate, function ($q, $date) {
                    return $q->whereDate('value_date_time', '>=', $date);
                });

                // Apply End Date filter
                $query->when($endDate, function ($q, $date) {
                    return $q->whereDate('value_date_time', '<=', $date);
                });

                // Apply Credit/Debit filter
                $query->when($creditDebitIndicator, function ($q, $indicator) {
                    return $q->where('credit_debit_indicator', $indicator);
                });

                // Apply the mandatory company filter
                $query->whereHas('account.company', function ($q) use ($user) {
                    return $q->where('code', $user['company_code']);
                });

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('account.bank.bank_name')
                    ->label('Bank'),
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

<?php

namespace App\Filament\Resources\AccountResource\Widgets;

use App\Models\Account;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class AccountStats extends BaseWidget
{
    protected static ?int $sort = 1; // Optional: Control widget order

    /**
     * The $filters property is automatically populated by Filament with the active
     * table filters from the parent page (e.g., ListRecords page).
     *
     * Make sure your List page's filters use the keys:
     * - 'account_number'
     * - 'start_date'
     * - 'end_date'
     */
    public ?array $filters = [];

    protected function getCards(): array
    {
        $user = Auth::user();

        // --- 1. Get filter values ---
        $accountNumber = $this->filters['account_number'] ?? null;
        $institutionCode = $this->filters['institution_code'] ?? null;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        // --- 2. Build the base queries and apply filters ---

        $accountQuery = Account::query()->where('company_code', $user['company_code']);
        $transactionQuery = Transaction::query()->whereHas('account.company', fn ($q) =>
        $q->where('code', $user['company_code'])
        );

        if ($institutionCode) {
            $accountQuery = $accountQuery->where('institution_code', $institutionCode);
        }

        if ($accountNumber) {
            $account = Account::find($accountNumber);
            if ($account) {
                $transactionQuery->where('account_identification', $account->account_identification);
            }
        }

        if ($startDate) {
            $transactionQuery->whereDate('value_date_time', '>=', $startDate);
        }
        if ($endDate) {
            $transactionQuery->whereDate('value_date_time', '<=', $endDate);
        }

        // --- 3. Execute queries and calculate totals ---

        // **FIXED**: If no start date is provided, use today's date for the opening balance.
        // This provides a sensible default view for the 'Beginning Balance' card.
        $openingBalanceDate = $startDate ?? now();

        // Get accounts with their opening balance.
        $accountsWithOpeningBalance = (clone $accountQuery)
            ->with(['balances' => function ($query) use ($openingBalanceDate) {
                $startOfDay = Carbon::parse($openingBalanceDate)->startOfDay();
                // We look for the last balance recorded *before* the start of the opening day.
                $query->where('date_time', '<', $startOfDay)
                    ->orderByDesc('date_time');
            }])->get();

        $openingBalance = $accountsWithOpeningBalance->sum(fn ($acc) => $acc->balances->first()?->amount ?? 0);
        $totalCredit = (clone $transactionQuery)->where('credit_debit_indicator', 'C')->sum('transaction_amount');
        $totalDebit = abs((clone $transactionQuery)->where('credit_debit_indicator', 'D')->sum('transaction_amount'));

        // --- 4. Calculate Closing Balance from the 'balances' table ---

        // Determine the target date for the closing balance.
        // If an end date is provided and it's in the past or today, use it.
        // Otherwise, use today's date. This handles future dates by fetching the latest available balance.
        $closingBalanceTargetDate = ($endDate && Carbon::parse($endDate)->isPast()) ? $endDate : now();

        // Start with the base account query for closing balance calculation.
        $closingBalanceAccountQuery = (clone $accountQuery);

        // IMPORTANT: Apply the account number filter if it exists.
        if ($accountNumber) {
            $closingBalanceAccountQuery->where('account_number', $accountNumber);
        }

        // Get the relevant accounts with their latest balance up to the end of the target date.
        // This correctly finds the latest balance on or before the target date (e.g., Friday's balance for a Sunday request).
        $accountsWithClosingBalance = $closingBalanceAccountQuery
            ->with(['balances' => function ($query) use ($closingBalanceTargetDate) {
                $endOfDay = Carbon::parse($closingBalanceTargetDate)->endOfDay();
                $query->where('date_time', '<=', $endOfDay)
                    ->orderByDesc('date_time'); // Order by date to get the most recent one first
            }])->get();

        // Sum the latest balance from each filtered account.
        $closingBalance = $accountsWithClosingBalance->sum(fn ($acc) => $acc->balances->first()?->amount ?? 0);

        // --- 4. Prepare descriptions and return Stat cards ---
        $descSuffix = ($accountNumber || $startDate || $endDate) ? ' (filtered)' : '';

        return [
            Stat::make('Beginning Balance', number_format($openingBalance, 2))
                ->description('Sum of opening balances' . $descSuffix),
            Stat::make('Total Debit Transactions', number_format($totalDebit, 2))
                ->description('Sum of all debit transactions' . $descSuffix)
                ->color('danger'),
            Stat::make('Total Credit Transactions', number_format($totalCredit, 2))
                ->description('Sum of all credit transactions' . $descSuffix)
                ->color('success'),
            Stat::make('Ending Balance', number_format($closingBalance, 2))
                ->description('Calculated ending balance' . $descSuffix),
        ];
    }
    #[On('updateWidgetData')]
    public function updateData($data)
    {
        $this->filters = $data;
    }
}

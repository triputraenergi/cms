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

//    protected function getCards(): array
//    {
//        $user = Auth::user();
//        // Get filter values, providing null as a default
//        $accountNumber = $this->filters['account_number'] ?? null;
//        $startDate = $this->filters['start_date'] ?? null;
//        $endDate = $this->filters['end_date'] ?? null;
//
//        $accounts = Account::where('company_code', $user['company_code'])->get();
//
//        // Start a new query for transactions
//        $transactionQuery = Transaction::query()->whereHas('account.company', fn ($query) =>
//            $query->where('code', $user['company_code'])
//        );
//
//        $accountsWithBalance = Account::where('company_code', $user['company_code'])->with(['balances' => function ($query) use ($startDate) {
//            // Use when() to apply conditional logic
//            $query->when($startDate,
//                // This function runs if $startDate IS NOT null or empty
//                function ($query) use ($startDate) {
//                    $query->whereDate('date_time', $startDate)->latest('date_time');
//                },
//                // This function runs if $startDate IS null or empty
//                function ($query) {
//                    $query->oldest('date_time');
//                }
//            )->limit(1); // limit(1) applies to both cases
//        }])->get();
//
//        // Apply account number filter if provided
//        if ($accountNumber) {
//            $transactionQuery->where('account_identification', $accountNumber);
//            $accountIdentification = Account::find($accountNumber)->account_identification;
//            $accounts = $accounts->where('account_identification', $accountIdentification);
//            $accountsWithBalance = $accountsWithBalance->where('account_identification', $accountIdentification);
//        }
//
//        // Apply date range filters if provided. Assumes a 'created_at' column.
//        if ($startDate) {
//            $transactionQuery->whereDate('value_date_time', '>=', $startDate);
//        }
//        if ($endDate) {
//            $transactionQuery->whereDate('value_date_time', '<=', $endDate);
//        }
//
//        // Calculate total credit and debit from the filtered transaction query
//        $totalCredit = (clone $transactionQuery)->where('credit_debit_indicator', 'C')->sum('transaction_amount');
//        $totalDebit = abs((clone $transactionQuery)->where('credit_debit_indicator', 'D')->sum('transaction_amount'));
//
//        // Dynamically update descriptions based on active filters
//        $openingBalanceDesc = 'Sum of opening balances';
//        $closingBalanceDesc = 'Sum of closing balances';
//        $creditDesc = 'Sum of all credit transactions';
//        $debitDesc = 'Sum of all debit transactions';
//        if ($accountNumber || $startDate || $endDate) {
//            $openingBalanceDesc .= ' (filtered)';
//            $closingBalanceDesc .= ' (filtered)';
//            $creditDesc .= ' (filtered)';
//            $debitDesc .= ' (filtered)';
//        }
//
//
//
//        // 2. Calculate the sum using a closure for safety
//        $openingBalance = $accountsWithBalance->sum(function ($account) {
//            return $account->balances->first()?->amount ?? 0;
//        });
//        $closingBalance = $accounts->sum('balance.amount');
//
//
//        return [
//            Stat::make('Beginning Balance', number_format($openingBalance, 2))
//                ->description($openingBalanceDesc),
//            Stat::make('Total Debit Transactions', number_format($totalDebit, 2))
//                ->description($debitDesc)
//                ->color('danger'),
//            Stat::make('Total Credit Transactions', number_format($totalCredit, 2))
//                ->description($creditDesc)
//                ->color('success'),
//            Stat::make('Closing Balance', number_format($closingBalance, 2))
//                ->description($closingBalanceDesc),
//        ];
//    }
    protected function getCards(): array
    {
        $user = Auth::user();

        // --- 1. Get filter values ---
        $accountNumber = $this->filters['account_number'] ?? null;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        // --- 2. Build the base queries and apply filters ---

        $accountQuery = Account::query()->where('company_code', $user['company_code']);
        $transactionQuery = Transaction::query()->whereHas('account.company', fn ($q) =>
        $q->where('code', $user['company_code'])
        );

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

        Log::info("Opening Balance Date : " . $openingBalanceDate);

//        // Get accounts with their opening balance based on the determined date.
//        $accountsWithOpeningBalance = (clone $accountQuery)
//            ->with(['balances' => function ($query) use ($openingBalanceDate) {
//                $endOfDay = \Carbon\Carbon::parse($openingBalanceDate)->endOfDay();
//                $query->where('date_time', '<=', $endOfDay)
//                    ->orderByDesc('date_time');
//            }])->get();
//
//        foreach ($accountsWithOpeningBalance as $account) {
//            $account->opening_balance = $account->balances->first(); // fallback to the latest available one
//        }
//
//        $openingBalance = $accountsWithOpeningBalance->sum(fn ($acc) => $acc->balances->first()?->amount ?? 0);
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
//        $closingBalance = $openingBalance + $totalCredit - $totalDebit;

        // --- 4. Calculate Closing Balance from the 'balances' table ---

        // Determine the target date for the closing balance.
        // If an end date is provided and it's in the past or today, use it.
        // Otherwise, use today's date. This handles future dates by fetching the latest available balance.
        $closingBalanceTargetDate = ($endDate && Carbon::parse($endDate)->isPast()) ? $endDate : now();

        // Start with the base account query for closing balance calculation.
        $closingBalanceAccountQuery = Account::query()->where('company_code', $user['company_code']);

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
            Stat::make('Closing Balance', number_format($closingBalance, 2))
                ->description('Calculated closing balance' . $descSuffix),
        ];
    }
    #[On('updateWidgetData')]
    public function updateData($data)
    {
        $this->filters = $data;
    }
}

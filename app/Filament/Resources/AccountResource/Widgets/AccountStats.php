<?php

namespace App\Filament\Resources\AccountResource\Widgets;

use App\Models\Account;
use App\Models\Transaction;
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
        // Get filter values, providing null as a default
        $accountNumber = $this->filters['account_number'] ?? null;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        $accounts = Account::where('company_code', $user['company_code'])->get();

        // Start a new query for transactions
        $transactionQuery = Transaction::query()->whereHas('account.company', fn ($query) =>
            $query->where('code', $user['company_code'])
        );

        $accountsWithBalance = Account::where('company_code', $user['company_code'])->with(['balances' => function ($query) use ($startDate) {
            // Use when() to apply conditional logic
            $query->when($startDate,
                // This function runs if $startDate IS NOT null or empty
                function ($query) use ($startDate) {
                    $query->whereDate('date_time', $startDate)->latest('date_time');
                },
                // This function runs if $startDate IS null or empty
                function ($query) {
                    $query->oldest('date_time');
                }
            )->limit(1); // limit(1) applies to both cases
        }])->get();

        // Apply account number filter if provided
        if ($accountNumber) {
            $transactionQuery->where('account_identification', $accountNumber);
            $accountIdentification = Account::find($accountNumber)->account_identification;
            $accounts = $accounts->where('account_identification', $accountIdentification);
            $accountsWithBalance = $accountsWithBalance->where('account_identification', $accountIdentification);
        }

        // Apply date range filters if provided. Assumes a 'created_at' column.
        if ($startDate) {
            $transactionQuery->whereDate('value_date_time', '>=', $startDate);
        }
        if ($endDate) {
            $transactionQuery->whereDate('value_date_time', '<=', $endDate);
        }

        // Calculate total credit and debit from the filtered transaction query
        $totalCredit = (clone $transactionQuery)->where('credit_debit_indicator', 'C')->sum('transaction_amount');
        $totalDebit = abs((clone $transactionQuery)->where('credit_debit_indicator', 'D')->sum('transaction_amount'));

        // Dynamically update descriptions based on active filters
        $openingBalanceDesc = 'Sum of opening balances';
        $closingBalanceDesc = 'Sum of closing balances';
        $creditDesc = 'Sum of all credit transactions';
        $debitDesc = 'Sum of all debit transactions';
        if ($accountNumber || $startDate || $endDate) {
            $openingBalanceDesc .= ' (filtered)';
            $closingBalanceDesc .= ' (filtered)';
            $creditDesc .= ' (filtered)';
            $debitDesc .= ' (filtered)';
        }



        // 2. Calculate the sum using a closure for safety
        $openingBalance = $accountsWithBalance->sum(function ($account) {
            return $account->balances->first()?->amount ?? 0;
        });
        $closingBalance = $accounts->sum('balance.amount');


        return [
            Stat::make('Beginning Balance', number_format($openingBalance, 2))
                ->description($openingBalanceDesc),
            Stat::make('Total Debit Transactions', number_format($totalDebit, 2))
                ->description($debitDesc)
                ->color('danger'),
            Stat::make('Total Credit Transactions', number_format($totalCredit, 2))
                ->description($creditDesc)
                ->color('success'),
            Stat::make('Closing Balance', number_format($closingBalance, 2))
                ->description($closingBalanceDesc),
        ];
    }

    #[On('updateWidgetData')]
    public function updateData($data)
    {
        $this->filters = $data;
    }
}

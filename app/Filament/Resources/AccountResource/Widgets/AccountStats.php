<?php

namespace App\Filament\Resources\AccountResource\Widgets;

use App\Models\Account;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
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
        // Get filter values, providing null as a default
        $accountNumber = $this->filters['account_number'] ?? null;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        $accounts = Account::all();

        // --- Transaction Calculations (Debit & Credit) ---

        // Start a new query for transactions
        $transactionQuery = Transaction::query();

        // Apply account number filter if provided
        if ($accountNumber) {
            $transactionQuery->where('account_identification', $accountNumber);
            $accountIdentification = Account::find($accountNumber)->account_identification;
            $accounts = $accounts->where('account_identification', $accountIdentification);
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
        $balanceDesc = 'Sum of latest balances';
        $transactionDesc = 'Sum of all transactions';
        if ($accountNumber || $startDate || $endDate) {
            $balanceDesc .= ' (filtered)';
            $transactionDesc .= ' (filtered)';
        }

        $totalBalance = $accounts->sum('balance.amount');


        return [
            Stat::make('Total Balance', number_format($totalBalance, 2))
                ->description($balanceDesc)
                ->color('success'),
            Stat::make('Total Credit Transactions', number_format($totalCredit, 2))
                ->description($transactionDesc)
                ->color('blue'),
            Stat::make('Total Debit Transactions', number_format($totalDebit, 2))
                ->description($transactionDesc)
                ->color('danger'),
        ];
    }

    #[On('updateWidgetData')]
    public function updateData($data)
    {
        $this->filters = $data;
    }
}

<?php

namespace App\Filament\Resources\AccountResource\Widgets;

use App\Models\Balance;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

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
    protected ?array $filters = [];

    protected function getStats(): array
    {
        // Get filter values, providing null as a default
        $accountNumber = $this->filters['account_number'] ?? null;
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

        // --- Balance Calculations (ITAV type) ---

        // Subquery to find the latest date for each account, respecting filters
        $latestBalanceDatesSubquery = Balance::select('account_identification', DB::raw('MAX(date_time) as latest_date'))
            ->where('balance_type', 'ITAV');

        // If an account number is specified, filter for that account.
        if ($accountNumber) {
            $latestBalanceDatesSubquery->where('account_identification', $accountNumber);
        }

        // If an end date is specified, find the latest balance *as of* that date.
        // The start date is not relevant for finding the single latest balance.
        if ($endDate) {
            $latestBalanceDatesSubquery->where('date_time', '<=', $endDate);
        }

        $latestBalanceDatesSubquery->groupBy('account_identification');

        // Base query to join 'ITAV' balances with their latest dates
        $baseQuery = DB::table('balances')
            ->joinSub($latestBalanceDatesSubquery, 'latest_bal', function ($join) {
                $join->on('balances.account_identification', '=', 'latest_bal.account_identification')
                    ->on('balances.date_time', '=', 'latest_bal.latest_date');
            })
            ->where('balance_type', 'ITAV');

        // Perform aggregations on the filtered balance data
        $totalBalance = (clone $baseQuery)->sum('balances.amount');
        $totalCreditLine = (clone $baseQuery)->sum('balances.credit_line_amount');

        // --- Transaction Calculations (Debit & Credit) ---

        // Start a new query for transactions
        $transactionQuery = Transaction::query();

        // Apply account number filter if provided
        if ($accountNumber) {
            $transactionQuery->where('account_identification', $accountNumber);
        }

        // Apply date range filters if provided. Assumes a 'created_at' column.
        if ($startDate) {
            $transactionQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $transactionQuery->whereDate('created_at', '<=', $endDate);
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


        return [
            Stat::make('Total Balance', number_format($totalBalance, 2))
                ->description($balanceDesc)
                ->color('success'),
            Stat::make('Total Credit Line', number_format($totalCreditLine, 2))
                ->description('Sum of available credit lines' . ($accountNumber ? ' (filtered)' : ''))
                ->color('warning'),
            Stat::make('Total Credit Transactions', number_format($totalCredit, 2))
                ->description($transactionDesc)
                ->color('blue'),
            Stat::make('Total Debit Transactions', number_format($totalDebit, 2))
                ->description($transactionDesc)
                ->color('danger'),
        ];
    }
}

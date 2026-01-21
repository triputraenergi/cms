<?php

namespace App\Filament\Resources\AccountResource\Widgets;

use App\Models\Account;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class AccountStats extends BaseWidget
{
    protected static ?int $sort = 1; // Optional: Control widget order

    public bool $isLoading = false;

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

    protected function getColumns(): int
    {
        return 2;
    }

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

        $companyCode = $user['company_code'];

        $transactionQuery = DB::table('transactions')
            ->leftJoin('currencies as curr', function ($join) {
                $join->on(DB::raw("transactions.transaction_currency COLLATE utf8mb4_unicode_ci"), '=', 'curr.code');
            })
            ->whereExists(function ($query) use ($companyCode) {
                $query->select(DB::raw(1))
                    ->from('accounts')
                    ->whereColumn('transactions.account_identification', 'accounts.account_number')
                    ->whereExists(function ($q) use ($companyCode) {
                        $q->select(DB::raw(1))
                            ->from('companies')
                            ->whereColumn('accounts.company_code', 'companies.code')
                            ->whereRaw("companies.code COLLATE utf8mb4_unicode_ci = ?", [$companyCode]);
                    });
            })
            ->whereBetween(DB::raw('DATE(value_date_time)'), [$startDate, $endDate]);

        if ($institutionCode) {
            $accountQuery = $accountQuery->where('institution_code', $institutionCode);
        }

        if ($accountNumber) {
            $account = Account::find($accountNumber);
            if ($account) {
                $transactionQuery = $transactionQuery->where('account_identification', $accountNumber);
            }
        }

        if ($startDate) {
            $transactionQuery = $transactionQuery->whereDate('value_date_time', '>=', $startDate);
        }
        if ($endDate) {
            $transactionQuery = $transactionQuery->whereDate('value_date_time', '<=', $endDate);
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
                $query->whereDate('date_time', '<=', $startOfDay)
                    ->orderByDesc('date_time');
            }])->get();


        $totalCredit = (clone $transactionQuery)
            ->where('credit_debit_indicator', 'C')
            ->selectRaw('SUM(transactions.transaction_amount * COALESCE(curr.conversion_rate, 1.0000)) AS total')
            ->value('total');

        $totalDebit = (clone $transactionQuery)
            ->where('credit_debit_indicator', 'D')
            ->selectRaw('SUM(transactions.transaction_amount * COALESCE(curr.conversion_rate, 1.0000)) AS total')
            ->value('total');
        // --- 4. Calculate Closing Balance from the 'balances' table ---

        // Determine the target date for the closing balance.
        // If an end date is provided and it's in the past or today, use it.
        // Otherwise, use today's date. This handles future dates by fetching the latest available balance.
        $closingBalanceTargetDate = ($endDate && Carbon::parse($endDate)->isPast()) ? $endDate : now();

        // Start with the base account query for closing balance calculation.
        $closingBalanceAccountQuery = (clone $accountQuery);

        // IMPORTANT: Apply the account number filter if it exists.
        if ($accountNumber) {
            $accountsWithOpeningBalance = $accountsWithOpeningBalance->where('account_number', $accountNumber);
            $closingBalanceAccountQuery->where('account_number', $accountNumber);
        }

        $openingBalance = $accountsWithOpeningBalance->sum(fn($acc) => $acc->balances->first()?->idr_amount ?? 0);

        // Get the relevant accounts with their latest balance up to the end of the target date.
        // This correctly finds the latest balance on or before the target date (e.g., Friday's balance for a Sunday request).
        // $isToday = $endDate && Carbon::parse($endDate)->isSameDay(now());

        $isTodayOrFuture = !$endDate
            || Carbon::parse($endDate)->greaterThanOrEqualTo(now()->startOfDay());

        $closingBalanceTargetDate = $endDate ?? now();

        $accountsWithClosingBalance = $closingBalanceAccountQuery
            ->with(['balances' => function ($query) use ($closingBalanceTargetDate, $isTodayOrFuture) {


                if ($isTodayOrFuture) {
                    // 🔥 KALAU endDate = NOW → ambil ITAV
                    $query->select(
                        'balance_id',
                        'account_identification',
                        'amount',
                        'date_time',
                        'balance_type'
                    )
                        ->where('balance_type', 'ITAV')
                        ->orderByDesc('date_time')
                        ->limit(1);
                } else {
                    // 📌 KALAU BUKAN TODAY → pakai snapshot biasa
                    $endOfDay = Carbon::parse($closingBalanceTargetDate)->endOfDay();

                    $query->select(
                        'balance_id',
                        'account_identification',
                        'amount',
                        'date_time',
                        'balance_type'
                    )
                        ->where('date_time', '>', $endOfDay)
                        ->orderBy('date_time', 'asc')
                        ->limit(1);
                }
            }])
            ->get();


        // Log::debug('closingBalance', [$accountsWithClosingBalance]);

        // Sum the latest balance from each filtered account.
        $closingBalance = $accountsWithClosingBalance->sum(fn($acc) => $acc->balances->first()?->idr_amount ?? 0);

        // --- 4. Prepare descriptions and return Stat cards ---
        $descSuffix = ($accountNumber || $startDate || $endDate) ? ' (filtered)' : '';

        return [
            Stat::make('Beginning Balance', 'Rp. ' . number_format($openingBalance, 2, ',', '.'))
                ->description('Sum of opening balances' . $descSuffix),
            Stat::make('Ending Balance', 'Rp. ' . number_format($closingBalance, 2, ',', '.'))
                ->description('Calculated ending balance' . $descSuffix),
            Stat::make('Total Debit Transactions', 'Rp. ' . number_format($totalDebit, 2, ',', '.'))
                ->description('Sum of all debit transactions' . $descSuffix)
                ->color('danger'),
            Stat::make('Total Credit Transactions', 'Rp. ' . number_format($totalCredit, 2, ',', '.'))
                ->description('Sum of all credit transactions' . $descSuffix)
                ->color('success'),

        ];
    }
    #[On('updateWidgetData')]
    public function updateData($data)
    {
        $this->filters = $data;
    }
}

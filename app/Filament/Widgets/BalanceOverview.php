<?php

namespace App\Filament\Widgets;

use App\Models\Balance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BalanceOverview extends BaseWidget
{
    protected static ?int $sort = 1; // Optional: Control widget order

    protected function getStats(): array
    {
        // This subquery is perfect, no changes needed
        $latestBalanceDatesSubquery = Balance::select('account_identification', DB::raw('MAX(date_time) as latest_date'))
            ->groupBy('account_identification');

        // This query correctly gets the total of the latest balances
        $totalBalance = DB::table('balances')
            ->joinSub($latestBalanceDatesSubquery, 'latest_bal', function ($join) {
                $join->on('balances.account_identification', '=', 'latest_bal.account_identification')
                    ->on('balances.date_time', '=', 'latest_bal.latest_date');
            })
            ->sum('balances.amount');

        // This query correctly gets the total of the latest credit lines
        $totalCredit = DB::table('balances')
            ->joinSub($latestBalanceDatesSubquery, 'latest_bal', function ($join) {
                $join->on('balances.account_identification', '=', 'latest_bal.account_identification')
                    ->on('balances.date_time', '=', 'latest_bal.latest_date');
            })
            ->sum('balances.credit_line_amount');

        // 👇 CORRECTED LOGIC for counting accounts with current positive/negative balances
        $baseQueryForCounts = DB::table('balances')
            ->joinSub($latestBalanceDatesSubquery, 'latest_bal', function ($join) {
                $join->on('balances.account_identification', '=', 'latest_bal.account_identification')
                    ->on('balances.date_time', '=', 'latest_bal.latest_date');
            });

        // Clone the base query to apply different conditions
        $positiveBalancesCount = (clone $baseQueryForCounts)->where('balances.amount', '>', 0)->count();
        $negativeBalancesCount = (clone $baseQueryForCounts)->where('balances.amount', '<', 0)->count();


        return [
            Stat::make('Total Balance', number_format($totalBalance, 2))
                ->description('Sum of latest balances across all accounts')
                ->color('success'),
            Stat::make('Total Credit Line', number_format($totalCredit, 2))
                ->description('Sum of all available credit lines')
                ->color('warning'),
            Stat::make('Accounts with Positive Balance', $positiveBalancesCount)
                ->description('Accounts with a current positive balance')
                ->color('primary'),
            Stat::make('Accounts with Negative Balance', $negativeBalancesCount)
                ->description('Accounts with a current negative balance')
                ->color('danger'),
        ];
    }
}

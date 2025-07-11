<?php

namespace App\Filament\Resources\AccountResource\Widgets;

use App\Models\Account;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountBalances extends BaseWidget
{
    protected function getStats(): array
    {
        $accounts = Account::all();
        $stats = [];

        foreach ($accounts as $account) {
            $stats[] = Stat::make($account->name, $account->balance);
        }

        return $stats;
    }
}

<?php

namespace App\Filament\Resources\BalanceResource\Widgets;

use App\Models\Balance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BalanceChart extends ChartWidget
{
    protected static ?string $heading = 'Chart';

    protected int | string | array $columnSpan = 'md';

    protected function getData(): array
    {
        $monthlyData = Balance::query()
            ->select(
                DB::raw("DATE_FORMAT(date_time, '%b') as month"), // Group by month abbreviation (e.g., 'Jan')
                DB::raw('sum(amount) as total_balance')
            )
            ->whereYear('date_time', now()->year)
            ->groupBy('month')
            ->orderByRaw("MIN(date_time)") // Order chronologically
            ->pluck('total_balance', 'month') // Creates an associative array: ['Jan' => 1500, 'Feb' => 2200, ...]
            ->all();

        // 2. Prepare the data structure for the chart
        // A complete list of months for the x-axis labels
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // Map the retrieved data to the full list of months.
        // If a month has no data, its value will default to 0.
        $data = array_map(function ($month) use ($monthlyData) {
            return $monthlyData[$month] ?? 0;
        }, $labels);


        // 3. Return the final data in the format required by ChartWidget
        return [
            'datasets' => [
                [
                    'label' => 'Balance',
                    'data' => $data,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => '#3b82f6', // A nice blue color
                    'tension' => 0.3, // Makes the line slightly curved
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

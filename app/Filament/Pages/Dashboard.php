<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AccountResource\Widgets\AccountStats;
use App\Filament\Resources\TransactionResource\Widgets\LatestTransactions;
use App\Models\Account;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Bank Statement Dashboard';

    protected static ?string $slug = 'dashboard-main';

    // Public properties to hold the state of our filters
    public ?array $filters = [
        'account_number' => null,
        'start_date' => null,
        'end_date' => null,
    ];

    public function mount(): void
    {
        // Initialize the form with default values or from query string
        $this->form->fill($this->filters);
    }

    /**
     * Defines the schema for the filter form.
     */
    protected function getFormSchema(): array
    {
        return [
            Grid::make(3)->schema([
                Select::make('filters.account_number')
                    ->label('Filter by Account')
                    ->options(Account::query()->pluck('account_number', 'account_number'))
                    ->searchable()
                    ->placeholder('All Accounts'),

                DatePicker::make('filters.start_date')
                    ->label('Start Date'),

                DatePicker::make('filters.end_date')
                    ->label('End Date')
                    ->afterStateUpdated(fn () => $this->applyFilters()),
            ]),
        ];
    }

    /**
     * This method is triggered when a filter value changes.
     * It re-renders the widgets with the new filter data.
     */
    public function applyFilters(): void
    {
        $this->getWidgets();
    }


    public function getSubheading(): string | Htmlable | null
    {
        return 'An overview of your bank accounts and transactions.';
    }

    /**
     * The widgets will now receive the $filters property from this page.
     */
    public function getWidgets(): array
    {
        return [
            AccountStats::class,
            LatestTransactions::class,
        ];
    }

    /**
     * Pass the filter data to any widget that has a public 'filters' property.
     */
    public function getWidgetData(): array
    {
                return [
            'filters' => $this->form->getState()['filters'],
        ];
    }


    public function getColumns(): int | array
    {
        return 2;
    }
}

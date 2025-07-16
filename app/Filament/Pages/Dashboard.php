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
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class Dashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.dashboard';
    protected static ?string $title = 'Dashboard';
    protected static ?string $slug = 'dashboard';

    /**
     * This public property is the single source of truth for the form's state.
     * It will be updated automatically by the form.
     */
    public ?array $filters = [
        'account_number' => null,
        'period' => 'custom',
        'start_date' => null,
        'end_date' => null,
        'credit_debit_indicator' => null,
    ];

    public function mount(): void
    {
        // Set initial dynamic dates. The form will automatically pick these up
        // because its state is bound to the $filters property.
        $this->filters['start_date'] = $this->filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $this->filters['end_date'] = $this->filters['end_date'] ?? now()->endOfMonth()->toDateString();
    }

    /**
     * This is the modern way to define a form. It replaces getFormSchema().
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(4)->schema([
                    // NOTE: The 'filters.' prefix is REMOVED from all component names.
                    Select::make('account_number')
                        ->label('Account')
                        ->options(Account::query()->pluck('account_number', 'account_number'))
                        ->searchable()
                        ->placeholder('All Accounts')
                        ->reactive()
                        ->afterStateUpdated(function ($state) {
                            $this->dispatch('updateWidgetData', data: $this->filters);
                        }),

                    Select::make('period')
                        ->label('Period')
                        ->options([
                            'today' => 'Today',
                            'week' => 'This Week',
                            'month' => 'This Month',
                            'year' => 'This Year',
                            'custom' => 'Custom',
                        ])
                        ->default('custom')
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            $now = Carbon::now();
                            // NOTE: The 'filters.' prefix is REMOVED from $set() calls.
                            switch ($state) {
                                case 'today':
                                    $set('start_date', $now->startOfDay()->toDateString());
                                    $set('end_date', $now->endOfDay()->toDateString());
                                    $this->dispatch('updateWidgetData', data: $this->filters);
                                    break;
                                case 'week':
                                    $set('start_date', $now->startOfWeek()->toDateString());
                                    $set('end_date', $now->endOfWeek()->toDateString());
                                    $this->dispatch('updateWidgetData', data: $this->filters);
                                    break;
                                case 'month':
                                    $set('start_date', $now->startOfMonth()->toDateString());
                                    $set('end_date', $now->endOfMonth()->toDateString());
                                    $this->dispatch('updateWidgetData', data: $this->filters);
                                    break;
                                case 'year':
                                    $set('start_date', $now->startOfYear()->toDateString());
                                    $set('end_date', $now->endOfYear()->toDateString());
                                    $this->dispatch('updateWidgetData', data: $this->filters);
                                    break;
                                default:
                                    $set('start_date', null);
                                    $set('end_date', null);
                                    $this->dispatch('updateWidgetData', data: $this->filters);
                                    break;
                            }
                        }),

                    DatePicker::make('start_date')
                        ->label('Start Date')
                        ->reactive()
                        ->visible(fn (callable $get) => $get('period') === 'custom')
                        ->afterStateUpdated(function ($state) {
                            $this->dispatch('updateWidgetData', data: $this->filters);
                        }),

                    DatePicker::make('end_date')
                        ->label('End Date')
                        ->reactive()
                        ->visible(fn (callable $get) => $get('period') === 'custom')
                        ->afterStateUpdated(function ($state) {
                            $this->dispatch('updateWidgetData', data: $this->filters);
                        }),
                    Select::make('credit_debit_indicator')
                        ->label('Type')
                        ->options([
                            'Credit' => 'Credit',
                            'Debit' => 'Debit'
                        ])
                        ->placeholder('Credit/Debit')
                        ->reactive()
                        ->afterStateUpdated(function ($state) {
                            $this->dispatch('updateWidgetData', data: $this->filters);
                        }),
                ]),
            ])
            /**
             * This is the most critical line. It tells the form to use the
             * public `$filters` property for its state.
             */
            ->statePath('filters');
    }

    /**
     * This is the CORRECT, REACTIVE way to pass data.
     * It's called automatically when the form state (bound to $this->filters) changes.
     */
    public function getWidgetData(): array
    {
        return [
            'filters' => $this->filters,
        ];
    }

    /**
     * This is the CORRECT way to define which widgets appear on the page.
     * Just list the class names.
     */
    public function getWidgets(): array
    {
        return [
            AccountStats::make([
                'filters' => $this->filters
            ]),
            LatestTransactions::make([
                'filters' => $this->filters
            ]),
        ];
    }

    public function getColumns(): int | array
    {
        return 2;
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'An overview of your bank accounts and transactions.';
    }
}

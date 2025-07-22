<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AccountResource\Widgets\AccountStats;
use App\Filament\Resources\BalanceResource\Widgets\BalanceChart;
use App\Filament\Resources\TransactionResource\Widgets\LatestTransactions;
use App\Models\Account;
use App\Models\Balance;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

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

    public ?string $lastRefreshed = null;
    public function mount(): void
    {
        // Set initial dynamic dates. The form will automatically pick these up
        // because its state is bound to the $filters property.
        $this->filters['start_date'] = $this->filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $this->filters['end_date'] = $this->filters['end_date'] ?? now()->endOfMonth()->toDateString();
        $lastBalance = Balance::query()->latest('updated_at')->first();
        $timestamp = $lastBalance->updated_at;
        $formattedDate = '';

        if ($timestamp->isToday()) {
            $formattedDate = 'today ' . $timestamp->format('H:i:s');
        } elseif ($timestamp->isYesterday()) {
            $formattedDate = 'yesterday ' . $timestamp->format('H:i:s');
        } else {
            $formattedDate = $timestamp->format('d-m-Y H:i:s');
        }

        // Now $this->lastRefreshed holds the conditionally formatted string.
        $this->lastRefreshed = $formattedDate;
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
                        ->options(Account::query()->where('company_code', Auth::user()['company_code'])->pluck('account_number', 'account_number'))
                        ->searchable()
                        ->placeholder('All Accounts')
                        ->reactive()
                        ->afterStateUpdated(function ($state) {
                            $this->dispatch('updateWidgetData', data: $this->filters);
                            $this->dispatch('refresh');
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
//                                    $set('start_date', null);
//                                    $set('end_date', null);
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
                ]),
            ])
            /**
             * This is the most critical line. It tells the form to use the
             * public `$filters` property for its state.
             */
            ->statePath('filters');
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->tooltip(fn () => 'Last refreshed at ' . $this->lastRefreshed)

                ->action(function (\Filament\Actions\Action $action) {
                    // 1. Before the logic runs, change the icon to the spinning version
                    $action->icon('heroicon-o-arrow-path-solid animate-spin');

                    // This makes the button temporarily unavailable to prevent double-clicking
                    $action->disabled();

                    // make post request to middleware api to refresh data
                    $response = Http::post(config("services.middleware.url") .'/api/hsbc/refresh?companyCode=' . auth()->user()->company_code);

                    // Optional: Check if the request was successful and handle errors
                    if ($response->failed()) {
                        // Handle error, maybe show a notification
                         Notification::make()->title('Refresh failed!')->danger()->send();
                    } else {
                        Notification::make()->title('Refreshed successfully!')->success()->send();
                    }
                    // 2. Your core logic to refresh the data
//                    $this->lastRefreshed = now()->tz('Asia/Jakarta')->format('g:i:s A');

                    $timestamp = now()->tz('Asia/Jakarta');
                    $formattedDate = '';

                    if ($timestamp->isToday()) {
                        $formattedDate = 'today ' . $timestamp->format('H:i:s');
                    } elseif ($timestamp->isYesterday()) {
                        $formattedDate = 'yesterday ' . $timestamp->format('H:i:s');
                    } else {
                        $formattedDate = $timestamp->format('d-m-Y H:i:s');
                    }

                    // Now $this->lastRefreshed holds the conditionally formatted string.
                    $this->lastRefreshed = $formattedDate;


                    // 3. After the logic is done, change the icon back to the original
                    $action->icon('heroicon-o-arrow-path');

                    // Re-enable the button
                    $action->disabled(false);
                    $this->dispatch('updateWidgetData', data: $this->filters);
                    $this->dispatch('$refresh');

                })
        ];

    }
}

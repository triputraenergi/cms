<?php

namespace App\Filament\Resources;

use App\Filament\Exports\TransactionExporter;
use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Account;
use App\Models\Transaction;
use Filament\Tables\Actions\ExportAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('account_identification')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('bank_transaction_code_code')
                    ->maxLength(10),
                Forms\Components\TextInput::make('bank_transaction_code_subcode')
                    ->maxLength(10),
                Forms\Components\DatePicker::make('booking_date_time')
                    ->required(),
                Forms\Components\TextInput::make('credit_debit_indicator')
                    ->required()
                    ->maxLength(1),
                Forms\Components\TextInput::make('proprietary_bank_transaction_code_code')
                    ->maxLength(10),
                Forms\Components\TextInput::make('proprietary_bank_transaction_code_issuer')
                    ->maxLength(10),
                Forms\Components\TextInput::make('requestor_account_id')
                    ->maxLength(50),
                Forms\Components\TextInput::make('reversal_indicator')
                    ->maxLength(1),
                Forms\Components\TextInput::make('statement_reference')
                    ->maxLength(50),
                Forms\Components\TextInput::make('transaction_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('transaction_currency')
                    ->required()
                    ->maxLength(3),
                Forms\Components\Textarea::make('transaction_information')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('transaction_reference')
                    ->maxLength(50),
                Forms\Components\TextInput::make('transaction_status')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('value_date_time')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account_identification')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bank_transaction_code_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bank_transaction_code_subcode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('booking_date_time')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit_debit_indicator')
                    ->searchable(),
                Tables\Columns\TextColumn::make('proprietary_bank_transaction_code_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('proprietary_bank_transaction_code_issuer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('requestor_account_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reversal_indicator')
                    ->searchable(),
                Tables\Columns\TextColumn::make('statement_reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value_date_time')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('From'),
                        DatePicker::make('created_until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('value_date_time', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('value_date_time', '<=', $date),
                            );
                    }),
                Tables\Filters\SelectFilter::make('account_identification')
                    ->label('Account ID')
                    ->options(
                        // This fetches all unique account identifications from your table
                        // to populate the dropdown.
                        Transaction::query()
                            ->distinct()
                            ->pluck('account_identification', 'account_identification')
                            ->all()
                    )
                    ->multiple() // Allows selecting multiple accounts to filter by
                    ->searchable(), // Makes the dropdown options searchable
                Tables\Filters\SelectFilter::make('credit_debit_indicator')
                    ->label('Credit/Debit')
                    ->options(
                        // This fetches all unique account identifications from your table
                        // to populate the dropdown.
                        Transaction::query()
                            ->distinct()
                            ->pluck('credit_debit_indicator', 'credit_debit_indicator')
                            ->all()
                    )
                    ->multiple() // Allows selecting multiple accounts to filter by
                    ->searchable() // Makes the dropdown options searchable
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(TransactionExporter::class)
                    ->fileName('transactions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}

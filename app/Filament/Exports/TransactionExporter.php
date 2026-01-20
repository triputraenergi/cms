<?php

namespace App\Filament\Exports;

use App\Models\Transaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TransactionExporter extends Exporter
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('transaction_id'),
            ExportColumn::make('account_identification'),
            ExportColumn::make('bank_transaction_code_code'),
            ExportColumn::make('bank_transaction_code_subcode'),
            ExportColumn::make('booking_date_time'),
            ExportColumn::make('credit_debit_indicator'),
            ExportColumn::make('proprietary_bank_transaction_code_code'),
            ExportColumn::make('proprietary_bank_transaction_code_issuer'),
            ExportColumn::make('requestor_account_id'),
            ExportColumn::make('reversal_indicator'),
            ExportColumn::make('statement_reference'),
            ExportColumn::make('transaction_amount')
                ->state(fn($record) => '=' . (int) $record->transaction_amount),
            ExportColumn::make('transaction_currency'),
            ExportColumn::make('transaction_information'),
            ExportColumn::make('transaction_reference'),
            ExportColumn::make('transaction_status'),
            ExportColumn::make('value_date_time'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your transaction export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}

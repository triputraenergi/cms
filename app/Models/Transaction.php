<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Transaction
 *
 * @package App\Models
 *
 * @property int $transaction_id
 * @property string $account_identification
 * @property string|null $requestor_account_id
 * @property string|null $transaction_reference
 * @property string|null $statement_reference
 * @property string $credit_debit_indicator
 * @property string|null $reversal_indicator
 * @property string $transaction_status
 * @property \Illuminate\Support\Carbon $booking_date_time
 * @property \Illuminate\Support\Carbon $value_date_time
 * @property string|null $transaction_information
 * @property float $transaction_amount
 * @property string $transaction_currency
 * @property string|null $bank_transaction_code_code
 * @property string|null $bank_transaction_code_subcode
 * @property string|null $proprietary_bank_transaction_code_code
 * @property string|null $proprietary_bank_transaction_code_issuer
 */
class Transaction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'transaction_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * Note: In Laravel, validation rules like @NotNull and @Size
     * are handled in Form Requests or Controller validation, not in the model.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_identification',
        'requestor_account_id',
        'transaction_reference',
        'statement_reference',
        'credit_debit_indicator',
        'reversal_indicator',
        'transaction_status',
        'booking_date_time',
        'value_date_time',
        'transaction_information',
        'transaction_amount',
        'transaction_currency',
        'bank_transaction_code_code',
        'bank_transaction_code_subcode',
        'proprietary_bank_transaction_code_code',
        'proprietary_bank_transaction_code_issuer',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'booking_date_time' => 'date',
        'value_date_time' => 'date',
        'transaction_amount' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the account that owns the transaction.
     * This is the equivalent of the @ManyToOne relationship.
     */
     public function account(): BelongsTo
     {
         return $this->belongsTo(Account::class, 'account_identification', 'account_number');
     }
}

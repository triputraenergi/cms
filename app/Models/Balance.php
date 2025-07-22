<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Balance
 *
 * @property int $balance_id
 * @property string $account_identification
 * @property string $balance_type
 * @property string $credit_debit_indicator
 * @property \Illuminate\Support\Carbon $date_time
 * @property float $amount
 * @property string $currency
 * @property bool $credit_line_included
 * @property float $credit_line_amount
 * @property-read \App\Models\Account|null $account
 */
class Balance extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'balances';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'balance_id';

    /**
     * Indicates if the model should be timestamped.
     * Laravel's default created_at and updated_at.
     * Set to false if you don't have these columns in your table.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_identification',
        'balance_type',
        'credit_debit_indicator',
        'date_time',
        'amount',
        'currency',
        'credit_line_included',
        'credit_line_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_time' => 'datetime',
        'amount' => 'decimal:4',
        'credit_line_included' => 'boolean',
        'credit_line_amount' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | This section defines the relationships for the model.
    |
    */

    /**
     * This relates to the account_number in the Accounts table.
     * If account_identification is always the account_number from the Accounts table,
     * you can define a belongsTo relationship here.
     *
     * Note: This assumes you have an 'Account' model and the foreign key
     * on the 'balances' table is 'account_identification' which references
     * the 'account_number' on the 'accounts' table.
     *
     * return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
     public function account(): BelongsTo
     {
         return $this->belongsTo(Account::class, 'account_identification', 'account_number');
     }
}

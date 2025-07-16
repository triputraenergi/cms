<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'accounts';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'account_number';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

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
        'account_number',
        'account_country',
        'account_type',
        'bic',
        'institution_code', // The foreign key for the Bank relationship
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the bank that the account belongs to.
     *
     * This defines the inverse of a one-to-many relationship (Many-to-One).
     */
    public function bank(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Bank::class, 'institution_code', 'institution_code');
    }

    /**
     * Get the collection of balances for this account.
     *
     * This defines a one-to-many relationship.
     */
    // public function balances(): HasMany
    // {
    //     // Assumes the foreign key on the 'balances' table is 'account_number'
    //     return $this->hasMany(Balance::class, 'account_number', 'account_number');
    // }

    /**
     * Get the collection of transactions for this account.
     *
     * This defines a one-to-many relationship.
     */
     public function transactions(): HasMany
     {
         // Assumes the foreign key on the 'transactions' table is 'account_identification' or a similar field.
         // Adjust the foreign key as needed.
         return $this->hasMany(Transaction::class, 'account_identification', 'account_number');
     }

     public function balance()
     {
         return $this->hasOne(Balance::class, 'account_identification', 'account_identification')->latest();
     }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getAccountIdentificationAttribute(): string
    {
        return $this->attributes['account_country'] . $this->attributes['institution_code'] . $this->attributes['account_type'] . $this->account_number;
    }
}

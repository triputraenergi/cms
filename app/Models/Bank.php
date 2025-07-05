<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Bank
 *
 * @package App\Models
 *
 * @property string $institution_code
 * @property string|null $bank_name
 * @property string|null $bic
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Account[] $accounts
 */
class Bank extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'banks';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'institution_code';

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
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_code',
        'bank_name',
        'bic',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get all of the accounts for the Bank.
     *
     * This defines a one-to-many relationship.
     */
    public function accounts(): HasMany
    {
        // The foreign key on the 'accounts' table is 'institution_code'
        // The local key on the 'banks' table is 'institution_code'
        return $this->hasMany(Account::class, 'institution_code', 'institution_code');
    }
}

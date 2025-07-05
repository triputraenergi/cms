<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankStatement extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',              // :20:
        'account_id',             // :25:
        'statement_number',       // :28C:
        'opening_balance',        // :60F:
        'closing_balance',
        'currency',               // Extracted from :60F:
        'opening_balance_date',   // Parsed from :60F:
    ];

    protected $casts = [
        'opening_balance_date' => 'date',
    ];

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }
}

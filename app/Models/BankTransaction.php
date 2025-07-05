<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    protected $fillable = [
        'bank_statement_id',
        'transaction_date',   // From :61:
        'type',               // 'D' or 'C' from :61:
        'amount',             // From :61:
        'reference',          // Parsed from :61:
        'description',        // From :86:
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function statement(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BankStatement::class, 'bank_statement_id');
    }
}

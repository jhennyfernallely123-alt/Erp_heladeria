<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashWithdrawal extends Model
{
    use HasFactory;

    /** Ver CashRegister::$dateFormat: el saldo de gaveta ordena por microsegundos. */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'user_id',
        'amount',
        'category',
        'reason',
        'receipt_number',
        'notes',
        'withdrawn_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'withdrawn_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

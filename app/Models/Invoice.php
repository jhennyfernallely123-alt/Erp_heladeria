<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    /** Ver CashRegister::$dateFormat: el saldo de gaveta ordena por microsegundos. */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'order_id',
        'invoice_number',
        'customer_name',
        'customer_doc_type',
        'customer_doc_number',
        'customer_email',
        'customer_phone',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'tip_amount',
        'total',
        'billing_mode',
        'dian_cufe',
        'dian_status',
        'pdf_path',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tip_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}

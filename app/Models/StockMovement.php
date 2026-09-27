<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_stock_id',
        'type',
        'quantity',
        'reason',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function stock()
    {
        return $this->belongsTo(ProductStock::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

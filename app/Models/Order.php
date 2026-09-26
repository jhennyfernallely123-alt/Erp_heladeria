<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'table_id',
        'user_id',
        'type',
        'status',
        'notes',
        'subtotal',
        'tax_total',
        'discount_total',
        'tip_amount',
        'total',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tip_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function table()
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public static function todayCount(): int
    {
        return static::whereDate('created_at', today())->count();
    }
}

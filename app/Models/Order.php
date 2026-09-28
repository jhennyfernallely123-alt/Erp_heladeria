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
        'work_shift_id',
        'type',
        'status',
        'notes',
        'delivery_name',
        'delivery_phone',
        'delivery_address',
        'delivery_notes',
        'delivery_fee',
        'subtotal',
        'tax_total',
        'discount_total',
        'tip_amount',
        'total',
    ];

    protected $casts = [
        'delivery_fee' => 'decimal:2',
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

    /**
     * Turno de mesero durante el que se tomo esta comanda.
     *
     * Es distinto de user_id: user_id es quien tenia la sesion iniciada en el
     * dispositivo, y este es quien estaba de turno y por lo tanto atendio.
     * En mostrador queda en null porque no hay turno de por medio.
     */
    public function workShift()
    {
        return $this->belongsTo(WorkShift::class, 'work_shift_id');
    }

    /** El pedido es a domicilio: tiene datos de entrega y tarifa de envio. */
    public function isDelivery(): bool
    {
        return $this->type === 'delivery';
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

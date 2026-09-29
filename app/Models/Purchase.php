<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_BOUGHT = 'bought';

    protected $fillable = [
        'supply_id',
        'supply_name',
        'unit',
        'quantity',
        'note',
        'status',
        'requested_by',
        'bought_at',
        'bought_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'bought_at' => 'datetime',
    ];

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'bought_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** "5 L" con la unidad ya abreviada, como en la vista de insumos. */
    public function quantityLabel(): string
    {
        $value = (float) $this->quantity;
        $formatted = rtrim(rtrim(number_format($value, 3, '.', ','), '0'), '.');

        $unit = ['kg' => 'kg', 'g' => 'g', 'L' => 'L', 'ml' => 'ml', 'units' => 'und'][$this->unit] ?? $this->unit;

        return $formatted.' '.$unit;
    }
}

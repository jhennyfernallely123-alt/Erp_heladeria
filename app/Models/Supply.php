<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    use HasFactory;

    public const UNITS = ['kg', 'g', 'L', 'ml', 'units'];

    protected $fillable = [
        'name',
        'unit',
        'quantity',
        'notes',
        'is_active',
        'counted_by',
        'counted_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'is_active' => 'boolean',
        'counted_at' => 'datetime',
    ];

    public function counter()
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    /** Etiqueta de la unidad, para mostrar "12 kg" y no "12 units". */
    public function unitLabel(): string
    {
        return [
            'kg' => 'kg',
            'g' => 'g',
            'L' => 'L',
            'ml' => 'ml',
            'units' => 'und',
        ][$this->unit] ?? $this->unit;
    }

    /** Cantidad con su unidad. Redondea los decimales que sobren. */
    public function quantityLabel(): string
    {
        $value = (float) $this->quantity;

        $formatted = rtrim(rtrim(number_format($value, 3, '.', ','), '0'), '.');

        return $formatted.' '.$this->unitLabel();
    }
}

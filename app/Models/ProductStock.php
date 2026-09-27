<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'quantity',
        'min_alert',
        'stock_type',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'min_alert' => 'decimal:2',
    ];

    protected $appends = ['status', 'formatted_quantity'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function status(): string
    {
        if ((float) $this->quantity <= 0) {
            return 'critical';
        }

        if ((float) $this->quantity <= (float) $this->min_alert) {
            return 'low';
        }

        return 'normal';
    }

    public function getStatusAttribute(): string
    {
        return $this->status();
    }

    public function getFormattedQuantityAttribute(): string
    {
        return $this->formattedQuantity();
    }

    public function formattedQuantity(): string
    {
        if ($this->stock_type === 'bulk_grams') {
            return rtrim(rtrim(number_format((float) $this->quantity, 3), '0'), '.') . ' kg';
        }

        return number_format((float) $this->quantity, 0);
    }

    public static function resolve(Product $product, ?ProductVariant $variant = null): self
    {
        return self::firstOrCreate(
            [
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
            ],
            [
                'quantity' => 0,
                'min_alert' => 5,
                'stock_type' => 'unit',
            ]
        );
    }
}

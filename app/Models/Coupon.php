<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_subtotal',
        'active',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_subtotal' => 'decimal:2',
            'active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function isUsable(): bool
    {
        return $this->active && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function discountFor(float $subtotal): float
    {
        if ($subtotal < (float) $this->min_subtotal) {
            return 0.0;
        }

        $discount = $this->type === 'percent'
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        // Pakistani rupees are quoted whole, matching the storefront's price format.
        return round(min($discount, $subtotal));
    }
}

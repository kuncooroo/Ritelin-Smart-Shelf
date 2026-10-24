<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'sku',
    'category',
    'unit_weight_grams',
    'sale_price',
    'image_path',
    'is_active',
])]
class Product extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_weight_grams' => 'decimal:1',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class);
    }

    public function imageUrl(): ?string
    {
        if ($this->image_path === null || $this->image_path === '') {
            return null;
        }

        return asset('uploads/'.$this->image_path);
    }

    public function priceLabel(): string
    {
        if ($this->sale_price === null) {
            return 'Belum diisi';
        }

        return 'Rp '.number_format((float) $this->sale_price, 0, ',', '.');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'api_product_id',
        'tozpie_product_id',
        'category',
        'description',
        'import_price',
        'selling_price',
        'price',
        'old_price',
        'duration',
        'stock',
        'in_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'api_product_id' => 'integer',
            'tozpie_product_id' => 'integer',
            'import_price' => 'integer',
            'selling_price' => 'integer',
            'old_price' => 'integer',
            'stock' => 'integer',
            'in_stock' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function sellingPrice(): int
    {
        return $this->selling_price ?: $this->price;
    }

    public function tozpieProductId(): int
    {
        return $this->tozpie_product_id ?: $this->api_product_id ?: $this->id;
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->oldest();
    }

    public function primaryImage(): ?ProductImage
    {
        return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
    }
}

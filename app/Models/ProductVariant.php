<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'size',
        'stock_quantity',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function displayName(): string
    {
        if (! $this->product) {
            return 'Produto Indisponível';
        }

        return "{$this->product->team_name} - {$this->product->season} - {$this->product->gender_label} - {$this->product->version_label} ({$this->size})";
    }

    public static function optionLabels(?string $search = null, int $limit = 50): array
    {
        return self::query()
            ->with('product')
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('product_variants.id', 'like', "%{$search}%")
                        ->orWhere('product_variants.size', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($query) use ($search) {
                            $query
                                ->where('team_name', 'like', "%{$search}%")
                                ->orWhere('season', 'like', "%{$search}%")
                                ->orWhere('gender', 'like', "%{$search}%")
                                ->orWhere('version', 'like', "%{$search}%");
                        });
                });
            })
            ->limit($limit)
            ->get()
            ->mapWithKeys(fn (ProductVariant $variant): array => [$variant->id => $variant->displayName()])
            ->all();
    }
}

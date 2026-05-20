<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'sales_order_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'is_customized',
        'custom_name',
        'custom_number',
        'sent_to_customization_at',
        'customization_stock_deducted_at',
    ];

    protected $casts = [
        'is_customized' => 'boolean',
        'sent_to_customization_at' => 'datetime',
        'customization_stock_deducted_at' => 'datetime',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function hasCustomizationStockDeducted(): bool
    {
        return $this->customization_stock_deducted_at !== null;
    }

    public function hasBeenSentToCustomization(): bool
    {
        return $this->sent_to_customization_at !== null;
    }

    public function customizationStockStatus(): string
    {
        if (! $this->is_customized) {
            return 'Sem personalização';
        }

        return $this->hasBeenSentToCustomization()
            ? 'Enviado'
            : 'Pendente';
    }
}

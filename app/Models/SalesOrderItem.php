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
    ];

    protected $casts = [
        'is_customized' => 'boolean',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}

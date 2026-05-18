<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'team_name',
        'season',
        'version',
        'description',
    ];

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function salesOrderItems()
    {
        return $this->hasManyThrough(SalesOrderItem::class, ProductVariant::class);
    }
}

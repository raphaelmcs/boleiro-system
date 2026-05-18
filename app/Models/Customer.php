<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'zip_code',
        'address',
        'city',
        'state',
    ];

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }
}

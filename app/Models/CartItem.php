<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $casts = [
        'addons' => 'array',
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function getRequiredOzAttribute()
    {
        return ($this->unit_price * $this->quantity) * 100;
    }
}

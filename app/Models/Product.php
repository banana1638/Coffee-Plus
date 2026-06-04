<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('images/products/' . $this->image);
        }
        return 'https://placehold.co/400x400?text=' . urlencode($this->name);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function addons()
    {
        return $this->hasMany(ProductAddon::class);
    }
}

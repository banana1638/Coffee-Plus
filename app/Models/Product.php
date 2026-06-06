<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $appends = ['image_url'];

    protected $casts = [
        'track_stock' => 'boolean',
    ];

    protected function price(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => [
                'price' => $value,
                'price_cents' => Money::toCents($value),
            ],
        );
    }

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

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function getAverageRatingAttribute(): float
    {
        return round((float) $this->reviews()->avg('rating'), 1);
    }

    public function getReviewsCountAttribute(): int
    {
        return (int) $this->reviews()->count();
    }
}

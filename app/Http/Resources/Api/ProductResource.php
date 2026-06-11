<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'base_price' => (float) $this->price,
            'base_price_cents' => (int) ($this->price_cents ?? round(((float) $this->price) * 100)),
            'category_id' => $this->menu_id,
            'is_available' => (bool) ($this->is_active ?? true),
            'addons' => $this->addons,
            'average_rating' => $this->average_rating,
            'reviews_count' => $this->reviews_count,
            'reviews' => $this->whenLoaded('reviews', fn () => $this->reviews->map(fn ($review) => [
                'rating' => $review->rating,
                'comment' => $review->comment,
                'user_name' => $review->user?->name,
                'created_at' => $review->created_at->format('Y-m-d'),
            ])),
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}


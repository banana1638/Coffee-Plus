<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'image_url' => $this->image ? asset(Storage::url($this->image)) : asset('images/default-coffee.png'),
            'base_price' => (float) $this->price,
            'category_id' => $this->menu_id,
            'is_available' => (bool) $this->is_active,
            'options' => $this->when($request->routeIs('*products.show*'), function () {
                return config('coffee.options');
            }),
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}

<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
                'image_url' => $this->product->image_url,
                'description' => $this->product->description ?? '',
            ],
            'size' => $this->size,
            'temp' => $this->temp,
            'addons' => $this->addons,
            'remark' => $this->remark,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}

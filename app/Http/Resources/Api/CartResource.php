<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'product' => new ProductResource($this->whenLoaded('product')),
            'quantity' => (int) $this->quantity,
            'size' => $this->size,
            'temp' => $this->temp,
            'addons' => $this->addons ?? [],
            'unit_price' => (float) $this->unit_price,
            'total_item_price' => (float) ($this->unit_price * $this->quantity),
        ];
    }
}

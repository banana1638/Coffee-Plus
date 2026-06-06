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
            'unit_price_cents' => (int) ($this->unit_price_cents ?? round(((float) $this->unit_price) * 100)),
            'total_item_price' => (float) ($this->unit_price * $this->quantity),
            'total_item_price_cents' => (int) (($this->unit_price_cents ?? round(((float) $this->unit_price) * 100)) * $this->quantity),
        ];
    }
}

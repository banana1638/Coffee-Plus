<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_name' => $this->product->name ?? 'Deleted Product',
            'quantity' => $this->quantity,
            'price_at_time' => (float) $this->price_at_time,
            'customizations' => [
                'size' => $this->size,
                'temp' => $this->temp,
                'addons' => $this->addons ?? [],
            ],
        ];
    }
}

<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'bill_id' => $this->bill_id,
            'amount' => (float) $this->final_amount,
            'status' => $this->status,
            'items' => $this->items->map(fn($item) => [
                'name' => $item->product->name,
                'qty' => $item->quantity,
                'price' => $item->price_at_time
            ]),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}

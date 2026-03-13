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
            'status' => $this->status,
            'subtotal' => number_format($this->subtotal, 2), 
            'total_amount' => number_format($this->subtotal, 2), 
            'oz_used' => (float) $this->oz_used,
            'final_amount' => number_format($this->final_amount, 2),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->format('M d, Y H:i'),
        ];
    }
}

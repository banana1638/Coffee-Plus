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
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'subtotal' => (float) $this->subtotal,
            'final_amount' => (float) $this->final_amount,
            'coupon_discount' => (float) ($this->coupon_discount ?? 0),
            'points_discount' => (float) ($this->points_discount ?? 0),
            'oz_used' => (float) ($this->oz_used ?? 0),
            'payment_method' => $this->payment_method,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}

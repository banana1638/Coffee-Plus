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
            'pickup_code' => $this->pickup_code,
            'pickup_qr_payload' => $this->pickup_qr_payload,
            'status' => $this->status,
            'status_step' => $this->statusStep(),
            'next_status' => $this->nextStatus(),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i'),
            'can_cancel' => $this->canBeCancelled(),
            'subtotal' => (float) $this->subtotal,
            'subtotal_cents' => (int) ($this->subtotal_cents ?? round(((float) $this->subtotal) * 100)),
            'final_amount' => (float) $this->final_amount,
            'final_amount_cents' => (int) ($this->final_amount_cents ?? round(((float) $this->final_amount) * 100)),
            'coupon_discount' => (float) ($this->coupon_discount ?? 0),
            'points_discount' => (float) ($this->points_discount ?? 0),
            'oz_used' => (float) ($this->oz_used ?? 0),
            'payment_method' => $this->payment_method,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}

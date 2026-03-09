<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'type' => $this->type,
            'oz_delta' => ($this->oz_delta > 0 ? '+' : '') . $this->oz_delta,
            'description' => $this->description,
            'time' => $this->created_at->diffForHumans(),
        ];
    }
}

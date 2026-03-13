<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // 从存储的 options 字段中安全获取数据
        $options = $this->options ?? [];

        return [
            'product_name' => $this->product->name ?? 'Deleted Product',
            'quantity' => $this->quantity,
            'price_at_time' => (float) $this->price_at_time,
            'oz_at_time' => (float) ($this->oz_at_time ?? 0), // 建议加上这个，方便 Flutter 判断
            'customizations' => [
                'size' => $options['size'] ?? 'N/A',
                'temp' => $options['temp'] ?? 'N/A',
                'addons' => $options['addons'] ?? [],
            ],
        ];
    }
}
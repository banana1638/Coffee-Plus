<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            // 如果你图片在 public/images/products，直接返回文件名或相对路径
            'image_url' => $this->image, 
            'base_price' => (float) $this->price,
            'category_id' => $this->menu_id,
            // 确保 is_active 在数据库里是 1
            'is_available' => (bool) ($this->is_active ?? true), 
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}

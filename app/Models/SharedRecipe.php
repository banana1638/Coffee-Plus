<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SharedRecipe extends Model
{
    protected $casts = [
        'addons' => 'array',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

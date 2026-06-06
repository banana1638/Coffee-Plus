<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('items_json');
            $table->integer('subtotal_cents');
            $table->integer('discount_cents')->default(0);
            $table->integer('oz_used')->default(0);
            $table->integer('final_amount_cents');
            $table->string('coupon_code')->nullable();
            $table->timestamp('pickup_time')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_snapshots');
    }
};

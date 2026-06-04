<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->index(['user_id', 'product_id'], 'favorites_user_id_product_id_idx');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('user_id', 'cart_items_user_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('cart_items_user_id_idx');
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->dropIndex('favorites_user_id_product_id_idx');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

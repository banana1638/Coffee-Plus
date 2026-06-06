<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('tangki_balance_cents')->default(0)->after('tangki_balance');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->integer('price_cents')->default(0)->after('price');
        });

        Schema::table('product_addons', function (Blueprint $table) {
            $table->integer('price_cents')->default(0)->after('price');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->integer('unit_price_cents')->nullable()->after('unit_price');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->integer('subtotal_cents')->default(0)->after('subtotal');
            $table->integer('final_amount_cents')->default(0)->after('final_amount');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('price_cents')->default(0)->after('price');
            $table->integer('price_at_time_cents')->default(0)->after('price_at_time');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->integer('value_cents')->nullable()->after('value');
        });

        DB::table('users')->update(['tangki_balance_cents' => DB::raw('ROUND(tangki_balance * 100)')]);
        DB::table('products')->update(['price_cents' => DB::raw('ROUND(price * 100)')]);
        DB::table('product_addons')->update(['price_cents' => DB::raw('ROUND(price * 100)')]);
        DB::table('cart_items')->update(['unit_price_cents' => DB::raw('ROUND(unit_price * 100)')]);
        DB::table('orders')->update([
            'subtotal_cents' => DB::raw('ROUND(subtotal * 100)'),
            'final_amount_cents' => DB::raw('ROUND(final_amount * 100)'),
        ]);
        DB::table('order_items')->update([
            'price_cents' => DB::raw('ROUND(price * 100)'),
            'price_at_time_cents' => DB::raw('ROUND(price_at_time * 100)'),
        ]);
        DB::table('coupons')->where('type', 'fixed')->update(['value_cents' => DB::raw('ROUND(value * 100)')]);
    }

    public function down(): void
    {
        Schema::table('coupons', fn (Blueprint $table) => $table->dropColumn('value_cents'));
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn(['price_cents', 'price_at_time_cents']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['subtotal_cents', 'final_amount_cents']));
        Schema::table('cart_items', fn (Blueprint $table) => $table->dropColumn('unit_price_cents'));
        Schema::table('product_addons', fn (Blueprint $table) => $table->dropColumn('price_cents'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('price_cents'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('tangki_balance_cents'));
    }
};

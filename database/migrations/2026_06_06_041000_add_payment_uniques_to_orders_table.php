<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_session_id')->nullable()->unique()->after('bill_id');
            $table->foreignId('cart_snapshot_id')->nullable()->unique()->after('payment_session_id')->constrained('cart_snapshots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cart_snapshot_id');
            $table->dropColumn('payment_session_id');
        });
    }
};

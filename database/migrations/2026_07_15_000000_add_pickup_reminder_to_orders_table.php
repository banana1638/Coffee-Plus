<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('pickup_reminder_sent_at')->nullable()->after('pickup_time');
            $table->index(
                ['status', 'pickup_reminder_sent_at', 'pickup_time'],
                'orders_pickup_reminder_lookup',
            );
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_pickup_reminder_lookup');
            $table->dropColumn('pickup_reminder_sent_at');
        });
    }
};

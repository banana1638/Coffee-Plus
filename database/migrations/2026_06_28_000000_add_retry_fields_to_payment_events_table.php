<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_events', function (Blueprint $table) {
            $table->unsignedInteger('retry_attempts')->default(0)->after('status');
            $table->string('last_error', 500)->nullable()->after('retry_attempts');
            $table->timestamp('last_retried_at')->nullable()->after('last_error');
        });
    }

    public function down(): void
    {
        Schema::table('payment_events', function (Blueprint $table) {
            $table->dropColumn(['retry_attempts', 'last_error', 'last_retried_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds HMAC-SHA256 blind index columns for searchable encrypted fields.
     * These allow WHERE queries without exposing plaintext values.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Blind index for phone — fixed-length SHA-256 hex (64 chars)
            $table->string('phone_index', 64)->nullable()->after('phone');
            // Blind index for address — not typically searched, but included for future use
            $table->string('address_index', 64)->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone_index', 'address_index']);
        });
    }
};

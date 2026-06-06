<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('referral_code')->nullable()->unique()->after('uuid');
            $table->foreignId('referred_by')->nullable()->after('referrer_id')->constrained('users')->nullOnDelete();
            $table->boolean('referral_rewarded')->default(false)->after('referred_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn(['referral_code', 'referred_by', 'referral_rewarded']);
        });
    }
};

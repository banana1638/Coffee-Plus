<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('direction');
            $table->integer('amount_cents');
            $table->integer('balance_before_cents');
            $table->integer('balance_after_cents');
            $table->string('source_type');
            $table->string('source_id')->nullable();
            $table->string('idempotency_key')->unique();
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_ledger');
    }
};

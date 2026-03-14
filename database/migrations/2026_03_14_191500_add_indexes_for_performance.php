<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Try to add indexes one by one to avoid stopping the whole migration on one failure
        
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('status');
            });
        } catch (\Exception $e) {
            // Log or ignore
        }

        try {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['user_id', 'type']);
            });
        } catch (\Exception $e) {
            // Log or ignore
        }

        try {
            Schema::table('transactions', function (Blueprint $table) {
                // Use a very specific name to avoid any chance of conflict
                $table->index('bill_id', 'idx_trans_bill_id_unique_perf');
            });
        } catch (\Exception $e) {
            // Log or ignore
        }

        try {
            Schema::table('products', function (Blueprint $table) {
                $table->index('is_active');
                $table->index('menu_id');
            });
        } catch (\Exception $e) {
            // Log or ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safely drop indexes
        $this->dropIndexIfExists('orders', 'orders_status_index');
        $this->dropIndexIfExists('transactions', 'transactions_user_id_type_index');
        $this->dropIndexIfExists('transactions', 'idx_trans_bill_id_unique_perf');
        $this->dropIndexIfExists('products', 'products_is_active_index');
        $this->dropIndexIfExists('products', 'products_menu_id_index');
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($index) {
                $table->dropIndex($index);
            });
        } catch (\Exception $e) {
            // Index likely doesn't exist
        }
    }
};

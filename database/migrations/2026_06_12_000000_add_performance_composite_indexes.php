<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('orders', ['status', 'updated_at'], 'orders_status_updated_at_idx');
        $this->addIndex('orders', ['user_id', 'created_at'], 'orders_user_id_created_at_idx');
        $this->addIndex('transactions', ['user_id', 'created_at'], 'transactions_user_id_created_at_idx');
        $this->addIndex('transactions', ['user_id', 'type', 'created_at'], 'transactions_user_type_created_at_idx');
        $this->addIndex('product_reviews', ['product_id', 'created_at'], 'product_reviews_product_id_created_at_idx');
        $this->addIndex('shared_recipes', ['recipient_id', 'created_at'], 'shared_recipes_recipient_id_created_at_idx');
        $this->addIndex('notifications', ['notifiable_type', 'notifiable_id', 'created_at'], 'notifications_notifiable_created_at_idx');
    }

    public function down(): void
    {
        $this->dropIndex('notifications', 'notifications_notifiable_created_at_idx');
        $this->dropIndex('shared_recipes', 'shared_recipes_recipient_id_created_at_idx');
        $this->dropIndex('product_reviews', 'product_reviews_product_id_created_at_idx');
        $this->dropIndex('transactions', 'transactions_user_type_created_at_idx');
        $this->dropIndex('transactions', 'transactions_user_id_created_at_idx');
        $this->dropIndex('orders', 'orders_user_id_created_at_idx');
        $this->dropIndex('orders', 'orders_status_updated_at_idx');
    }

    private function addIndex(string $table, array $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $name) {
                $table->index($columns, $name);
            });
        } catch (Throwable) {
            // Keep deploys tolerant of environments where an index was added manually.
        }
    }

    private function dropIndex(string $table, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($name) {
                $table->dropIndex($name);
            });
        } catch (Throwable) {
            // Index may not exist in older or manually maintained environments.
        }
    }
};

<?php

use App\Support\AddonsSignature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('addons_signature', 64)->nullable()->after('addons');
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->string('addons_signature', 64)->nullable()->after('addons');
        });

        $this->backfill('cart_items');
        $this->backfill('favorites');

        Schema::table('cart_items', function (Blueprint $table) {
            $table->index(['user_id', 'product_id', 'size', 'temp', 'addons_signature'], 'cart_items_options_signature_idx');
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->index(['user_id', 'product_id', 'size', 'temp', 'addons_signature'], 'favorites_options_signature_idx');
        });
    }

    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropIndex('favorites_options_signature_idx');
            $table->dropColumn('addons_signature');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('cart_items_options_signature_idx');
            $table->dropColumn('addons_signature');
        });
    }

    private function backfill(string $table): void
    {
        DB::table($table)
            ->select(['id', 'addons'])
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    $addons = json_decode($row->addons ?? '[]', true);
                    $addons = is_array($addons) ? $addons : [];

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['addons_signature' => AddonsSignature::from($addons)]);
                }
            });
    }
};

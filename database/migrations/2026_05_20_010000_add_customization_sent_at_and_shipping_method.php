<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->timestamp('sent_to_customization_at')->nullable()->after('custom_number');
        });

        DB::table('sales_order_items')
            ->whereNotNull('customization_stock_deducted_at')
            ->update(['sent_to_customization_at' => DB::raw('customization_stock_deducted_at')]);

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->enum('shipping_method', ['correios', 'uber'])
                ->default('correios')
                ->after('dispatch_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropColumn('sent_to_customization_at');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('shipping_method');
        });
    }
};

<?php

use App\Enums\StockLocation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Record where the stock is held. Existing rows default to the shop.
 *
 * Each table is listed explicitly (not looped) so static analysis can read the new column.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fabric_receiving_items', function (Blueprint $table) {
            $table->string('location', 30)->default(StockLocation::Shop->value);
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->string('location', 30)->default(StockLocation::Shop->value)->index();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('location', 30)->default(StockLocation::Shop->value);
        });

        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->string('location', 30)->default(StockLocation::Shop->value);
        });

        Schema::table('sales_return_items', function (Blueprint $table) {
            $table->string('location', 30)->default(StockLocation::Shop->value);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fabric_receiving_items', function (Blueprint $table) {
            $table->dropColumn('location');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropIndex(['location']);
            $table->dropColumn('location');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('location');
        });

        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropColumn('location');
        });

        Schema::table('sales_return_items', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};

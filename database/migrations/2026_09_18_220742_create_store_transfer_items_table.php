<?php

use App\Models\Catalog\Product;
use App\Models\Stock\StoreTransfer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('store_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(StoreTransfer::class);
            $table->foreignIdFor(Product::class);
            $table->decimal('qty', 7)->nullable(false);
            $table->decimal('size', 5)->nullable(false);
            $table->string('unit', 20)->nullable(false);
            $table->decimal('price', 10)->nullable(false);
            $table->decimal('expense', 10)->nullable(false)->default(0);
            $table->decimal('total_qty', 9)->nullable(false);
            $table->decimal('total_amount', 10)->nullable(false);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('store_transfer_id')->references('id')->on('store_transfers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_transfer_items');
    }
};

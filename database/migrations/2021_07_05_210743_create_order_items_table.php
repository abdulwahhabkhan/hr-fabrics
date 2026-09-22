<?php

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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('qty')->nullable(false);
            $table->decimal('size', 5, 2)->nullable(false);
            $table->string('unit', 20)->nullable(false);
            $table->decimal('price', 10, 2)->nullable(false);
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->decimal('total_qty', 9, 2)->nullable(false);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount')->default(0);
            $table->string('commission', 10)->default(0);
            $table->decimal('total_commission')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('order_id')->references('id')->on('orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};

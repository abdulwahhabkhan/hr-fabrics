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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('product_id');
            $table->string('voucher_no', 30);
            $table->string('unit', 30);
            $table->decimal('size', 7, 2);
            $table->integer('qty');
            $table->double('price');
            $table->decimal('total_qty');
            $table->decimal('total', 10, 2);
            $table->foreign('purchase_id')->on('purchases')->references('id');
            $table->foreign('product_id')->on('products')->references('id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};

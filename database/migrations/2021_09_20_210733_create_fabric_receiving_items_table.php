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
        Schema::create('fabric_receiving_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fabric_receiving_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('voucher_no', 30);
            $table->float('size');
            $table->string('unit', 30);
            $table->integer('qty');
            $table->float('total_qty');
            $table->tinyInteger('status')->default(0)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fabric_receiving_items');
    }
};

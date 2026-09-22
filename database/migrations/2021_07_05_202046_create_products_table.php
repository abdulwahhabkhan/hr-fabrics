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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brand_id')->nullable(false);
            $table->string('name', 200)->index()->nullable(false);
            $table->string('description', 250)->nullable();
            $table->string('finish', 50)->nullable(false);
            $table->boolean('is_box')->default(0)->index()->nullable(false);
            $table->integer('unit_price')->default(0);
            $table->float('size')->default(0);
            $table->integer('suit_price')->nullable()->default(0);
            $table->unsignedBigInteger('created_by')->nullable(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

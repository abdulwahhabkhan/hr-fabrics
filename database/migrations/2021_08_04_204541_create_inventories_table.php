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
        if (Schema::hasTable('inventories')) {
            Schema::rename('inventories', 'inventories_bk');
        }

        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->morphs('stockable');
            $table->unsignedInteger('stockable_item_id');
            $table->unsignedInteger('parent_id')->nullable();
            /*$table->string('type', '20')->index();
            $table->integer('record_id')->nullable(false);
            $table->string('ref_no', 20)->index();*/
            $table->unsignedInteger('product_id')->index();
            $table->nullableMorphs('outbound');
            $table->unsignedBigInteger('outbound_item_id')->nullable();
            $table->dateTime('outbound_on')->index()->nullable();
            $table->string('unit', 20);
            $table->decimal('size', 6);
            $table->json('info');
            $table->integer('qty')->default(0);
            $table->decimal('meters', 7)->default(0);
            $table->decimal('cost')->nullable();
            $table->dateTime('transaction_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};

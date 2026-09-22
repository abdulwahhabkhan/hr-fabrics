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
        Schema::create('value_additions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->index();
            $table->foreignId('vendor_id')->index();
            $table->foreignId('packed_by')->index();
            $table->string('lot_number')->index();
            $table->string('material_detail');
            $table->string('packing_detail');
            $table->string('cp_detail');
            $table->integer('cost');
            $table->float('meter');
            $table->float('packing_qty');
            $table->integer('packing_cost');
            $table->float('cp_meter');
            $table->integer('cp_cost');
            $table->integer('total_value');
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('value_additions');
    }
};

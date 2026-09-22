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
        Schema::create('biltis', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no', 20)->index();
            $table->string('bilti_no', 20)->index();
            $table->unsignedInteger('supplier_id')->index();
            $table->unsignedInteger('created_by')->index();
            $table->json('photos')->default(null);
            $table->json('items')->default(null);
            $table->double('total_qty');
            $table->double('total_meters');
            $table->enum('status', ['Open', 'Closed', 'Cancelled'])->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biltis');
    }
};

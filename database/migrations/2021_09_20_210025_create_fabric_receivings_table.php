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
        Schema::create('fabric_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 20)->index();
            $table->string('bilti_no', 30)->index()->nullable();
            $table->string('lot_no', 30)->nullable()->index();
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('supplier_id')->index();
            $table->json('photos')->nullable();
            $table->json('info')->nullable();
            $table->decimal('total_qty')->default(0);
            $table->decimal('total_meters')->default(0);
            $table->dateTime('transaction_date')->nullable()->index();
            $table->boolean('invoiced')->default(false);
            $table->enum('status', ['Open', 'Close', 'Cancel'])->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fabric_receivings');
    }
};

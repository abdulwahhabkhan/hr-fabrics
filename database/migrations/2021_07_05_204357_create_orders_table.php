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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('invoice_sr')->nullable(false);
            $table->string('invoice_no', 30)->index()->nullable(false);
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('agent_id')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->json('agent_rate')->nullable();
            $table->string('payment_mode', 30);
            $table->integer('customer_discount')->default(0);
            $table->decimal('discount_rate')->default(0);
            $table->integer('total')->default(0);
            $table->integer('total_qty')->default(0);
            $table->integer('net_total')->default(0);
            $table->decimal('commission')->default(0);
            $table->decimal('discount')->default(0);
            $table->integer('paid')->default(0);
            $table->string('purchase_type', 20)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

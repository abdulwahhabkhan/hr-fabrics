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
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->integer('sr');
            $table->string('invoice_no', 20)->index();
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('customer_id');
            $table->string('order_no')->index();
            $table->string('payment_mode')->nullable();
            $table->json('info')->nullable(true);
            $table->integer('agent_id')->nullable();
            $table->json('agent_rate')->nullable();
            $table->decimal('commission')->default(0);
            $table->decimal('total_qty')->default(0);
            $table->integer('amount')->default(0);
            $table->integer('expenses')->default(0);
            $table->integer('discount')->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->bigInteger('balance')->nullable();
            $table->dateTime('transaction_date')->nullable()->index();
            $table->tinyInteger('status')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};

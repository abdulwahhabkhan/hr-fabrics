<?php

use App\Enums\StatusText;
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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id')->index();
            $table->unsignedBigInteger('created_by')->index()->default(0);
            $table->unsignedBigInteger('stock_id')->index();
            $table->string('stock_ids')->nullable();
            $table->string('bilti_no', 150)->index();
            $table->string('lot_no', 250)->nullable()->index();
            $table->string('invoice_no', 150)->index();
            $table->string('bill_no', 30)->index()->nullable();
            $table->float('total_qty', 7)->default(0);
            $table->integer('discount')->default(0);
            $table->integer('total')->default(0);
            $table->integer('total_return')->default(0);
            $table->string('remarks')->nullable();
            $table->tinyInteger('paid')->default(0);
            $table->string('status', 30)->default(StatusText::Open->value)->index();
            $table->dateTime('transaction_date')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};

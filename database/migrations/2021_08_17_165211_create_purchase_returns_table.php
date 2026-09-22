<?php

use App\Enums\ReturnStatus;
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
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->integer('sr');
            $table->string('invoice_no', 20)->index();
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('supplier_id');
            $table->string('bilti_no')->index();
            $table->string('bill_no')->index();
            $table->json('info')->nullable();
            $table->decimal('total_qty')->default(0);
            $table->decimal('total', 10)->default(0);
            $table->decimal('discount')->default(0);
            $table->decimal('expenses')->default(0);
            $table->decimal('total_amount', 10)->default(0);
            $table->tinyInteger('status')->default(ReturnStatus::Open->value);
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
        Schema::dropIfExists('purchase_returns');
    }
};

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
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no', 30)->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('unit', 20);
            $table->decimal('size', 6)->default(0);
            $table->integer('qty')->default(0);
            $table->decimal('meters', 9)->default(0);
            $table->string('from_location', 30)->index();
            $table->string('to_location', 30)->index();
            $table->string('status', 20)->default(StatusText::Open->value)->index();
            $table->string('notes')->nullable();
            $table->date('transaction_date')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('confirmed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};

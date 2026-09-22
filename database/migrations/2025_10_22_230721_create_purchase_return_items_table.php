<?php

use App\Models\Catalog\Product;
use App\Models\Purchase\PurchaseReturn;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PurchaseReturn::class)->index();
            $table->foreignIdFor(Product::class)->index();
            $table->string('unit', 30);
            $table->float('size', 7, 2);
            $table->integer('qty');
            $table->float('total_qty');
            $table->double('rate');
            $table->float('total_amount', 10, 2);
            $table->timestamps();
        });
    }
};

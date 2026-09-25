<?php

use App\Models\Catalog\Product;
use App\Models\Sales\SalesReturn;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $view = 'sales_return_items';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->down();
        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(SalesReturn::class)->index();
            $table->foreignIdFor(Product::class)->index();
            $table->string('unit', 20);
            $table->decimal('size', 6);
            $table->decimal('qty')->default(0);
            $table->decimal('total_qty')->default(0);
            $table->decimal('rate')->default(0);
            $table->string('commission', 10);
            $table->decimal('total_commission')->default(0);
            $table->decimal('total_amount')->default(0);
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS {$this->view};");
    }
};

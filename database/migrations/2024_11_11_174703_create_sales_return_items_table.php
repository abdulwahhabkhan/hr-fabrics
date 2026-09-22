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
        // DB::statement($this->createView());
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

    private function createView(): string
    {

        $sql = "
SELECT  srtn.id as sales_return_id,
 ri.product_id,
 ri.product_name,
 ri.finish,
 ri.unit,
 ri.size,
 ri.rate,
 ri.qty,
 ri.total_qty
FROM sales_returns srtn,
     JSON_TABLE(srtn.items, '$[*]' COLUMNS (
                product_id VARCHAR(200)  PATH '$.product.product_id',
                product_name VARCHAR(200)  PATH '$.name',
                finish VARCHAR(100) PATH '$.product.finish',
                unit VARCHAR(30) PATH '$.unit',
                size double PATH '$.size',
                rate double PATH '$.rate',
                qty double PATH '$.qty',
                total_qty double PATH '$.total_qty')
     ) ri";

        return <<<EOD
        CREATE VIEW {$this->view} AS
            $sql
        EOD;
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'cp_stock', // view
        'cp_sale_items',
        'cp_sales_return_items',
        'cp_sales_returns',
        'cp_sales',
        'cp_invoice_return_items',
        'cp_invoice_returns',
        'cp_invoice_items',
        'cp_invoices',
        'cp_purchase_items',
        'cp_stocks',
        'cp_purchases',
        'cp_inventories',
        'cp_inventories_bk',
        'cut_pieces',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS cp_stock');
        foreach ($this->tables as $table) {
            if ($table !== 'cp_stock') {
                Schema::dropIfExists($table);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

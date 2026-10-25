<?php

use App\Enums\StockLocation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables whose rows record where the stock is held. Existing rows default to the shop.
     *
     * @var array<int, string>
     */
    private array $tables = [
        'fabric_receiving_items',
        'inventories',
        'order_items',
        'purchase_return_items',
        'sales_return_items',
        'store_transfer_items',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $column = $table->string('location', 30)->default(StockLocation::Shop->value);
                if ($tableName === 'inventories') {
                    $column->index();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if ($tableName === 'inventories') {
                    $table->dropIndex(['location']);
                }
                $table->dropColumn('location');
            });
        }
    }
};

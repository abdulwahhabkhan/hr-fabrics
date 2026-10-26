<?php

use App\Enums\OrderAllocation;
use App\Models\Sales\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks whether a sale order's meters are booked against stock. Existing orders are backfilled from the stock
 * currently booked to them.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('allocation')->default(OrderAllocation::Unallocated->value)->after('status');
            $table->index(['status', 'allocation']);
        });

        DB::table('orders')
            ->joinSub(
                DB::table('order_items')->selectRaw('order_id, ROUND(SUM(total_qty), 2) as required')->groupBy('order_id'),
                'required_meters', 'required_meters.order_id', '=', 'orders.id'
            )
            ->joinSub(
                DB::table('inventories')
                    ->selectRaw('outbound_id, ROUND(SUM(meters), 2) as allocated')
                    ->where('outbound_type', Order::morphClass())
                    ->groupBy('outbound_id'),
                'allocated_meters', 'allocated_meters.outbound_id', '=', 'orders.id'
            )
            ->where('allocated_meters.allocated', '>', 0)
            ->update([
                'allocation' => DB::raw(sprintf(
                    'CASE WHEN allocated_meters.allocated >= required_meters.required THEN %d ELSE %d END',
                    OrderAllocation::Allocated->value,
                    OrderAllocation::PartiallyAllocated->value,
                )),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'allocation']);
            $table->dropColumn('allocation');
        });
    }
};

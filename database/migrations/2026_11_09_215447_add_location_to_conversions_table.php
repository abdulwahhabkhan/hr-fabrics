<?php

use App\Enums\StockLocation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Record where a conversion cuts its stock and the day it happened. Existing rows default to the shop.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversions', function (Blueprint $table) {
            $table->string('location', 30)->default(StockLocation::Shop->value)->after('sku');
            $table->date('transaction_date')->nullable()->after('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversions', function (Blueprint $table) {
            $table->dropColumn(['location', 'transaction_date']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('store_transfer_items');
        Schema::dropIfExists('store_transfers');
    }

    /**
     * Reverse the migrations.
     *
     * Store transfers have been removed in favour of stock transfers, so there is nothing to restore.
     */
    public function down(): void {}
};

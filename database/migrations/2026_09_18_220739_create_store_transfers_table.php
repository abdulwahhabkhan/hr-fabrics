<?php

use App\Enums\StoreTransferStatus;
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
        Schema::create('store_transfers', function (Blueprint $table) {
            $table->id();
            $table->integer('transfer_sr')->nullable(false);
            $table->string('transfer_no', 30)->index()->nullable(false);
            $table->unsignedBigInteger('account_id');
            $table->json('details')->nullable();
            $table->date('confirmed_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->string('payment_mode')->nullable();
            $table->string('notes')->nullable();
            $table->decimal('total_qty')->default(0);
            $table->integer('expenses')->default(0);
            $table->integer('discount_on_total')->default(0);
            $table->integer('net_total')->default(0);
            $table->integer('total')->default(0);
            $table->string('type')->nullable();
            $table->integer('status')->default(StoreTransferStatus::Open->value)->index();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_transfers');
    }
};

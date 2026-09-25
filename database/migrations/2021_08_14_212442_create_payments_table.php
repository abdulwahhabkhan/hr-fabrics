<?php

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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->integer('sr')->default(0);
            $table->string('ref_no', 30)->index();
            $table->unsignedInteger('created_by')->index();
            $table->unsignedInteger('account_id')->index();
            $table->string('type', 30)->index();
            $table->string('reason');
            $table->json('payment_info');
            $table->float('amount');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

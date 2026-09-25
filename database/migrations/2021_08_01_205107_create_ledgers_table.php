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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('record_id')->nullable(false)->index();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('module', 30)->index();
            $table->string('type', 30)->index();
            $table->json('detail');
            $table->float('dr')->default(0);
            $table->float('cr')->default(0);
            $table->timestamps();

            $table->unique(['record_id', 'module', 'account_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
